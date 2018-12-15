<?php

namespace SwooleTW\Http\Concerns;


use Exception;
use Illuminate\Contracts\Container\Container;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Arr;
use SwooleTW\Http\Transformers\Request;
use SwooleTW\Http\Websocket\HandlerContract;
use SwooleTW\Http\Websocket\Parser;
use SwooleTW\Http\Websocket\Rooms\RoomContract;
use SwooleTW\Http\Websocket\Websocket;
use Throwable;

/**
 * Trait InteractsWithWebsocket
 *
 * @property \Illuminate\Contracts\Container\Container $container
 * @property \Illuminate\Contracts\Container\Container $app
 */
trait InteractsWithWebsocket
{
    /**
     * @var boolean
     */
    protected $isWebsocket = false;

    /**
     * @var \SwooleTW\Http\Websocket\HandlerContract
     */
    protected $websocketHandler;

    /**
     * @var \SwooleTW\Http\Websocket\Parser
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
            $this->app->make('swoole.websocket')->reset(true)->setSender($swooleRequest->fd);
            // set currnt request to sandbox
            $this->app->make('swoole.sandbox')->setRequest($illuminateRequest);
            // enable sandbox
            $this->app->make('swoole.sandbox')->enable();
            // check if socket.io connection established
            if (!$this->websocketHandler->onOpen($swooleRequest->fd, $illuminateRequest)) {
                return;
            }
            // trigger 'connect' websocket event
            if ($this->app->make('swoole.websocket')->eventExists('connect')) {
                // set sandbox container to websocket pipeline
                $this->app->make('swoole.websocket')->setContainer($this->app->make('swoole.sandbox')->getApplication());
                $this->app->make('swoole.websocket')->call('connect', $illuminateRequest);
            }
        } catch (Throwable $e) {
            $this->logServerError($e);
        } finally {
            // disable and recycle sandbox resource
            $this->app->make('swoole.sandbox')->disable();
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

            $this->app->make('swoole.websocket')->reset(true)->setSender($frame->fd);

            // enable sandbox
            $this->app->make('swoole.sandbox')->enable();

            $event = Arr::get($payload, 'event');
            $data = Arr::get($payload, 'data');
            // dispatch message to registered event callback
            if ($this->app->make('swoole.websocket')->eventExists($event)) {
                $this->app->make('swoole.websocket')->call($event, $data);
            } else {
                $this->websocketHandler->onMessage($frame);
            }
        } catch (Throwable $e) {
            $this->logServerError($e);
        } finally {
            // disable and recycle sandbox resource
            $this->app->make('swoole.sandbox')->disable();
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
        if (!$this->isWebsocket($fd) || !$server instanceof Websocket) {
            return;
        }

        try {
            $this->app->make('swoole.websocket')->reset(true)->setSender($fd);
            // trigger 'disconnect' websocket event
            if ($this->app->make('swoole.websocket')->eventExists('disconnect')) {
                $this->app->make('swoole.websocket')->call('disconnect');
            } else {
                $this->websocketHandler->onClose($fd, $reactorId);
            }
            // leave all rooms
            $this->app->make('swoole.websocket')->leave();
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
        if (!$broadcast && $sender && !in_array($sender, $fds)) {
            $fds[] = $sender;
        }

        // check if to broadcast all clients
        if ($broadcast && empty($fds) && !$assigned) {
            foreach ($server->connections as $fd) {
                if ($this->isWebsocket($fd)) {
                    $fds[] = $fd;
                }
            }
        }

        // push message to designated fds
        foreach ($fds as $fd) {
            if (($broadcast && $sender === (integer)$fd) || !$server->exist($fd)) {
                continue;
            }
            $server->push($fd, $message, $opcode);
        }
    }

    /**
     * Set frame parser for websocket.
     *
     * @param \SwooleTW\Http\Websocket\Parser $parser
     *
     * @return \SwooleTW\Http\Concerns\InteractsWithWebsocket
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
        $isWebsocket = $this->container->make('config')->get('swoole_http.websocket.enabled');
        $parser = $this->container->make('config')->get('swoole_websocket.parser');

        if ($isWebsocket) {
            array_push($this->events, ...$this->wsEvents);
            $this->isWebsocket = true;
            $this->setParser(new $parser);
        }
    }

    /**
     * Check if it's a websocket fd.
     *
     * @param int $fd
     *
     * @return bool
     */
    protected function isWebsocket(int $fd): bool
    {
        $info = $this->container->make('swoole.server')->connection_info($fd);

        return Arr::has($info, 'websocket_status') && Arr::get($info, 'websocket_status');
    }

    /**
     * Prepare websocket handler for onOpen and onClose callback.
     *
     * @throws \Exception
     */
    protected function prepareWebsocketHandler()
    {
        $handlerClass = $this->container->make('config')->get('swoole_websocket.handler');

        if (!$handlerClass) {
            throw new Exception('Websocket handler is not set in swoole_websocket config');
        }

        $this->setWebsocketHandler($this->app->make($handlerClass));
    }

    /**
     * Set websocket handler.
     *
     * @param \SwooleTW\Http\Websocket\HandlerContract $handler
     *
     * @return \SwooleTW\Http\Concerns\InteractsWithWebsocket
     */
    public function setWebsocketHandler(HandlerContract $handler)
    {
        $this->websocketHandler = $handler;

        return $this;
    }

    /**
     * Get websocket handler.
     *
     * @return \SwooleTW\Http\Websocket\HandlerContract
     */
    public function getWebsocketHandler(): HandlerContract
    {
        return $this->websocketHandler;
    }

    /**
     * Bind room instance to Laravel app container.
     */
    protected function bindRoom(): void
    {
        $this->app->singleton(RoomContract::class, function (Container $container) {
            $driver = $container->make('config')->get('swoole_websocket.default');
            $settings = $container->make('config')->get("swoole_websocket.settings.{$driver}");
            $className = $container->make('config')->get("swoole_websocket.drivers.{$driver}");

            return tap(new $className($settings))->prepare();
        });

        $this->app->alias(RoomContract::class, 'swoole.room');
    }

    /**
     * Bind websocket instance to Laravel app container.
     */
    protected function bindWebsocket()
    {
        $this->app->singleton(Websocket::class, function (Container $app) {
            return new Websocket($app->make('swoole.room'), new Pipeline($app));
        });

        $this->app->alias(Websocket::class, 'swoole.websocket');
    }

    /**
     * Load websocket routes file.
     */
    protected function loadWebsocketRoutes()
    {
        $routePath = $this->container->make('config')->get('swoole_websocket.route_file');

        if (!file_exists($routePath)) {
            $routePath = __DIR__ . '/../../routes/websocket.php';
        }

        return require $routePath;
    }

    /**
     * Normalize data for message push.
     *
     * @param array $data
     *
     * @return array
     */
    public function normalizePushData(array $data)
    {
        $opcode = Arr::get($data, 'opcode', 1);
        $sender = Arr::get($data, 'sender', 0);
        $fds = Arr::get($data, 'fds', []);
        $broadcast = Arr::get($data, 'broadcast', false);
        $assigned = Arr::get($data, 'assigned', false);
        $event = Arr::get($data, 'event', null);
        $message = Arr::get($data, 'message', null);

        return [$opcode, $sender, $fds, $broadcast, $assigned, $event, $message];
    }
}
