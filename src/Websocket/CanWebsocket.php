<?php

namespace SwooleTW\Http\Websocket;

use Exception;
use Swoole\Websocket\Frame;
use Swoole\Websocket\Server;
use Illuminate\Pipeline\Pipeline;
use SwooleTW\Http\Server\Request;
use SwooleTW\Http\Websocket\Parser;
use Illuminate\Support\Facades\Facade;
use SwooleTW\Http\Websocket\Websocket;
use SwooleTW\Http\Websocket\HandlerContract;
use SwooleTW\Http\Websocket\Rooms\RoomContract;

trait CanWebsocket
{
    /**
     * @var boolean
     */
    protected $isWebsocket = false;

    /**
     * @var SwooleTW\Http\Websocket\HandlerContract
     */
    protected $websocketHandler;

    /**
     * @var SwooleTW\Http\Websocket\Websocket
     */
    protected $websocket;

    /**
     * @var SwooleTW\Http\Websocket\Rooms\RoomContract
     */
    protected $websocketRoom;

    /**
     * @var SwooleTW\Http\Websocket\Parser
     */
    protected $parser;

    /**
     * Websocket server events.
     *
     * @var array
     */
    protected $wsEvents = ['open', 'message', 'close'];

    /**
     * "onOpen" listener.
     *
     * @param \Swoole\Websocket\Server $server
     * @param \Swoole\Http\Request $swooleRequest
     */
    public function onOpen(Server $server, $swooleRequest)
    {
        $illuminateRequest = Request::make($swooleRequest)->toIlluminate();

        try {
            $this->websocket->reset(true)->setSender($swooleRequest->fd);
            // set currnt request to sandbox
            $this->sandbox->setRequest($illuminateRequest);
            // enable sandbox
            $this->sandbox->enable();
            $application = $this->sandbox->getLaravelApp();
            // check if socket.io connection established
            if (! $this->websocketHandler->onOpen($swooleRequest->fd, $illuminateRequest)) {
                return;
            }
            // trigger 'connect' websocket event
            if ($this->websocket->eventExists('connect')) {
                // set sandbox container to websocket pipeline
                $this->websocket->setContainer($application);
                $this->websocket->call('connect', $illuminateRequest);
            }
        } catch (Exception $e) {
            $this->logServerError($e);
        } finally {
            // disable and recycle sandbox resource
            $this->sandbox->disable();
        }
    }

    /**
     * "onMessage" listener.
     *
     * @param \Swoole\Websocket\Server $server
     * @param \Swoole\Websocket\Frame $frame
     */
    public function onMessage(Server $server, Frame $frame)
    {
        $data = $frame->data;

        try {
            // execute parser strategies and skip non-message packet
            if ($this->parser->execute($server, $frame)) {
                return;
            }

            // decode raw message via parser
            $payload = $this->parser->decode($frame);

            $this->websocket->reset(true)->setSender($frame->fd);

            // enable sandbox
            $this->sandbox->enable();

            // dispatch message to registered event callback
            if ($this->websocket->eventExists($payload['event'])) {
                $this->websocket->call($payload['event'], $payload['data']);
            } else {
                $this->websocketHandler->onMessage($frame);
            }
        } catch (Exception $e) {
            $this->logServerError($e);
        } finally {
            // disable and recycle sandbox resource
            $this->sandbox->disable();
        }
    }

    /**
     * "onClose" listener.
     *
     * @param \Swoole\Websocket\Server $server
     * @param int $fd
     * @param int $reactorId
     */
    public function onClose(Server $server, $fd, $reactorId)
    {
        if (! $this->isWebsocket($fd)) {
            return;
        }

        try {
            $this->websocket->reset(true)->setSender($fd);
            // trigger 'disconnect' websocket event
            if ($this->websocket->eventExists('disconnect')) {
                $this->websocket->call('disconnect');
            } else {
                $this->websocketHandler->onClose($fd, $reactorId);
            }
            // leave all rooms
            $this->websocket->leave();
        } catch (Exception $e) {
            $this->logServerError($e);
        }
    }

    /**
     * Push websocket message to clients.
     *
     * @param \Swoole\Websocket\Server $server
     * @param mixed $data
     */
    public function pushMessage(Server $server, array $data)
    {
        [$opcode, $sender, $fds, $broadcast, $assigned, $event, $message] = $this->normalizePushData($data);
        $message = $this->parser->encode($event, $message);

        // attach sender if not broadcast
        if (! $broadcast && $sender && ! in_array($sender, $fds)) {
            $fds[] = $sender;
        }

        // check if to broadcast all clients
        if ($broadcast && empty($fds) && ! $assigned) {
            foreach ($server->connections as $fd) {
                if ($this->isWebsocket($fd)) {
                    $fds[] = $fd;
                }
            }
        }

        // push message to designated fds
        foreach ($fds as $fd) {
            if (($broadcast && $sender === (integer) $fd) || ! $server->exist($fd)) {
                continue;
            }
            $server->push($fd, $message, $opcode);
        }
    }

    /**
     * Set frame parser for websocket.
     *
     * @param \SwooleTW\Http\Websocket\Parser $parser
     */
    public function setParser(Parser $parser)
    {
        $this->parser = $parser;

        return $this;
    }

    /**
     * Get frame parser for websocket.
     */
    public function getParser()
    {
        return $this->parser;
    }

    /**
     * Check if is a websocket fd.
     */
    protected function isWebsocket(int $fd)
    {
        $info = $this->server->connection_info($fd);

        return array_key_exists('websocket_status', $info) && $info['websocket_status'];
    }

    /**
     * Set websocket handler for onOpen and onClose callback.
     */
    protected function setWebsocketHandler()
    {
        $handlerClass = $this->container['config']->get('swoole_websocket.handler');

        if (! $handlerClass) {
            throw new Exception('websocket handler not set in swoole_websocket config');
        }

        $handler = $this->app->make($handlerClass);

        if (! $handler instanceof HandlerContract) {
            throw new Exception(sprintf('%s must implement %s', get_class($handler), HandlerContract::class));
        }

        $this->websocketHandler = $handler;
    }

    /**
     * Set websocket handler for onOpen and onClose callback.
     */
    protected function setWebsocketRoom()
    {
        $driver = $this->container['config']->get('swoole_websocket.default');
        $configs = $this->container['config']->get("swoole_websocket.settings.{$driver}");
        $className = $this->container['config']->get("swoole_websocket.drivers.{$driver}");

        $this->websocketRoom = new $className($configs);
        $this->websocketRoom->prepare();
    }

    /**
     * Bind room instance to Laravel app container.
     */
    protected function bindRoom()
    {
        $this->app->singleton(RoomContract::class, function ($app) {
            return $this->websocketRoom;
        });
        $this->app->alias(RoomContract::class, 'swoole.room');
    }

    /**
     * Bind websocket instance to Laravel app container.
     */
    protected function bindWebsocket()
    {
        $this->app->singleton(Websocket::class, function ($app) {
            return $this->websocket = new Websocket($app['swoole.room'], new Pipeline);
        });
        $this->app->alias(Websocket::class, 'swoole.websocket');
    }

    /**
     * Load websocket routes file.
     */
    protected function loadWebsocketRoutes()
    {
        $routePath = $this->container['config']->get('swoole_websocket.route_file');

        if (! file_exists($routePath)) {
            $routePath = __DIR__ . '/../../routes/websocket.php';
        }

        return require $routePath;
    }

    /**
     * Normalize data for message push.
     */
    protected function normalizePushData(array $data)
    {
        $opcode = $data['opcode'] ?? 1;
        $sender = $data['sender'] ?? 0;
        $fds = $data['fds'] ?? [];
        $broadcast = $data['broadcast'] ?? false;
        $assigned = $data['assigned'] ?? false;
        $event = $data['event'] ?? null;
        $message = $data['message'] ?? null;

        return [$opcode, $sender, $fds, $broadcast, $assigned, $event, $message];
    }
}
