<?php

namespace SwooleTW\Http\Concerns;

use Throwable;
use Swoole\Websocket\Frame;
use Swoole\Websocket\Server;
use Illuminate\Pipeline\Pipeline;
use SwooleTW\Http\Websocket\Parser;
use Illuminate\Support\Facades\Facade;
use SwooleTW\Http\Websocket\Websocket;
use SwooleTW\Http\Transformers\Request;
use SwooleTW\Http\Websocket\HandlerContract;
use SwooleTW\Http\Websocket\Rooms\RoomContract;

trait InteractsWithWebsocket
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
    public function onOpen($server, $swooleRequest)
    {
        $illuminateRequest = Request::make($swooleRequest)->toIlluminate();

        try {
            $this->app['swoole.websocket']->reset(true)->setSender($swooleRequest->fd);
            // set currnt request to sandbox
            $this->app['swoole.sandbox']->setRequest($illuminateRequest);
            // enable sandbox
            $this->app['swoole.sandbox']->enable();
            // check if socket.io connection established
            if (! $this->websocketHandler->onOpen($swooleRequest->fd, $illuminateRequest)) {
                return;
            }
            // trigger 'connect' websocket event
            if ($this->app['swoole.websocket']->eventExists('connect')) {
                // set sandbox container to websocket pipeline
                $this->app['swoole.websocket']->setContainer($this->app['swoole.sandbox']->getApplication());
                $this->app['swoole.websocket']->call('connect', $illuminateRequest);
            }
        } catch (Throwable $e) {
            $this->logServerError($e);
        } finally {
            // disable and recycle sandbox resource
            $this->app['swoole.sandbox']->disable();
        }
    }

    /**
     * "onMessage" listener.
     *
     * @param \Swoole\Websocket\Server $server
     * @param \Swoole\Websocket\Frame $frame
     */
    public function onMessage($server, $frame)
    {
        try {
            // execute parser strategies and skip non-message packet
            if ($this->parser->execute($server, $frame)) {
                return;
            }

            // decode raw message via parser
            $payload = $this->parser->decode($frame);

            $this->app['swoole.websocket']->reset(true)->setSender($frame->fd);

            // enable sandbox
            $this->app['swoole.sandbox']->enable();

            // dispatch message to registered event callback
            if ($this->app['swoole.websocket']->eventExists($payload['event'])) {
                $this->app['swoole.websocket']->call($payload['event'], $payload['data']);
            } else {
                $this->websocketHandler->onMessage($frame);
            }
        } catch (Throwable $e) {
            $this->logServerError($e);
        } finally {
            // disable and recycle sandbox resource
            $this->app['swoole.sandbox']->disable();
        }
    }

    /**
     * "onClose" listener.
     *
     * @param \Swoole\Websocket\Server $server
     * @param int $fd
     * @param int $reactorId
     */
    public function onClose($server, $fd, $reactorId)
    {
        if (! $this->isWebsocket($fd)) {
            return;
        }

        try {
            $this->app['swoole.websocket']->reset(true)->setSender($fd);
            // trigger 'disconnect' websocket event
            if ($this->app['swoole.websocket']->eventExists('disconnect')) {
                $this->app['swoole.websocket']->call('disconnect');
            } else {
                $this->websocketHandler->onClose($fd, $reactorId);
            }
            // leave all rooms
            $this->app['swoole.websocket']->leave();
        } catch (Throwable $e) {
            $this->logServerError($e);
        }
    }

    /**
     * Push websocket message to clients.
     *
     * @param \Swoole\Websocket\Server $server
     * @param mixed $data
     */
    public function pushMessage($server, array $data)
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
     * Prepare settings if websocket is enabled.
     */
    protected function prepareWebsocket()
    {
        $isWebsocket = $this->container['config']->get('swoole_http.websocket.enabled');
        $parser = $this->container['config']->get('swoole_websocket.parser');

        if ($isWebsocket) {
            array_push($this->events, ...$this->wsEvents);
            $this->isWebsocket = true;
            $this->setParser(new $parser);
        }
    }

    /**
     * Check if it's a websocket fd.
     */
    protected function isWebsocket(int $fd)
    {
        $info = $this->container['swoole.server']->connection_info($fd);

        return array_key_exists('websocket_status', $info) && $info['websocket_status'];
    }

    /**
     * Prepare websocket handler for onOpen and onClose callback.
     */
    protected function prepareWebsocketHandler()
    {
        $handlerClass = $this->container['config']->get('swoole_websocket.handler');

        if (! $handlerClass) {
            throw new Exception('Websocket handler is not set in swoole_websocket config');
        }

        $this->setWebsocketHandler($this->app->make($handlerClass));
    }

    /**
     * Set websocket handler.
     */
    public function setWebsocketHandler(HandlerContract $handler)
    {
        $this->websocketHandler = $handler;

        return $this;
    }

    /**
     * Get websocket handler.
     */
    public function getWebsocketHandler()
    {
        return $this->websocketHandler;
    }

    /**
     * Get websocket handler for onOpen and onClose callback.
     */
    protected function getWebsocketRoom()
    {
        $driver = $this->container['config']->get('swoole_websocket.default');
        $configs = $this->container['config']->get("swoole_websocket.settings.{$driver}");
        $className = $this->container['config']->get("swoole_websocket.drivers.{$driver}");

        $websocketRoom = new $className($configs);
        $websocketRoom->prepare();

        return $websocketRoom;
    }

    /**
     * Bind room instance to Laravel app container.
     */
    protected function bindRoom()
    {
        $this->app->singleton(RoomContract::class, function ($app) {
            return $this->getWebsocketRoom();
        });
        $this->app->alias(RoomContract::class, 'swoole.room');
    }

    /**
     * Bind websocket instance to Laravel app container.
     */
    protected function bindWebsocket()
    {
        $this->app->singleton(Websocket::class, function ($app) {
            return new Websocket($app['swoole.room'], new Pipeline($app));
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
    public function normalizePushData(array $data)
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
