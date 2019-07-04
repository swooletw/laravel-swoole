<?php

namespace SwooleTW\Http\Concerns;

use Throwable;
use Illuminate\Pipeline\Pipeline;
use SwooleTW\Http\Server\Sandbox;
use SwooleTW\Http\Websocket\Parser;
use SwooleTW\Http\Websocket\Pusher;
use SwooleTW\Http\Websocket\Websocket;
use SwooleTW\Http\Transformers\Request;
use SwooleTW\Http\Server\Facades\Server;
use SwooleTW\Http\Websocket\HandlerContract;
use Illuminate\Contracts\Container\Container;
use Swoole\WebSocket\Server as WebsocketServer;
use SwooleTW\Http\Websocket\Rooms\RoomContract;
use SwooleTW\Http\Exceptions\WebsocketNotSetInConfigException;

/**
 * Trait InteractsWithWebsocket
 *
 * @property \Illuminate\Contracts\Container\Container $container
 * @property \Illuminate\Contracts\Container\Container $app
 * @property array $types
 */
trait InteractsWithWebsocket
{
    /**
     * @var boolean
     */
    protected $isServerWebsocket = false;

    /**
     * @var \SwooleTW\Http\Websocket\HandlerContract
     */
    protected $websocketHandler;

    /**
     * @var \SwooleTW\Http\Websocket\Parser
     */
    protected $payloadParser;

    /**
     * @var \SwooleTW\Http\Websocket\Rooms\RoomContract
     */
    protected $websocketRoom;

    /**
     * Websocket server events.
     *
     * @var array
     */
    protected $wsEvents = ['open', 'message', 'close'];

    /**
     * "onHandShake" listener.
     * @param \Swoole\Http\Request $swooleRequest
     * @param \Swoole\Http\Response $response
     */
    public function onHandShake($swooleRequest, $response)
    {
        $this->onOpen(
            $this->app->make(Server::class),
            $swooleRequest,
            $response
        );
    }

    /**
     * "onOpen" listener.
     *
     * @param \Swoole\Websocket\Server $server
     * @param \Swoole\Http\Request $swooleRequest
     * @param \Swoole\Http\Response $response (optional)
     */
    public function onOpen($server, $swooleRequest, $response = null)
    {
        $illuminateRequest = Request::make($swooleRequest)->toIlluminate();
        $websocket = $this->app->make(Websocket::class);
        $sandbox = $this->app->make(Sandbox::class);
        $handshakeHandler = $this->app->make('config')
            ->get('swoole_websocket.handshake.handler');

        try {
            $websocket->reset(true)->setSender($swooleRequest->fd);
            // set currnt request to sandbox
            $sandbox->setRequest($illuminateRequest);
            // enable sandbox
            $sandbox->enable();
            // call customized handshake handler
            if ($response && ! $this->app->make($handshakeHandler)->handle($swooleRequest, $response)) {
                return;
            }
            // check if socket.io connection established
            if (! $this->websocketHandler->onOpen($swooleRequest->fd, $illuminateRequest)) {
                return;
            }
            // trigger 'connect' websocket event
            if ($websocket->eventExists('connect')) {
                // set sandbox container to websocket pipeline
                $websocket->setContainer($sandbox->getApplication());
                $websocket->call('connect', $illuminateRequest);
            }
        } catch (Throwable $e) {
            $this->logServerError($e);
        } finally {
            // disable and recycle sandbox resource
            $sandbox->disable();
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
        // execute parser strategies and skip non-message packet
        if ($this->payloadParser->execute($server, $frame)) {
            return;
        }

        $websocket = $this->app->make(Websocket::class);
        $sandbox = $this->app->make(Sandbox::class);

        try {
            // decode raw message via parser
            $payload = $this->payloadParser->decode($frame);

            $websocket->reset(true)->setSender($frame->fd);

            // enable sandbox
            $sandbox->enable();

            // dispatch message to registered event callback
            ['event' => $event, 'data' => $data] = $payload;
            $websocket->eventExists($event)
                ? $websocket->call($event, $data)
                : $this->websocketHandler->onMessage($frame);
        } catch (Throwable $e) {
            $this->logServerError($e);
        } finally {
            // disable and recycle sandbox resource
            $sandbox->disable();
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
        if (! $this->isServerWebsocket($fd) || ! $server instanceof WebsocketServer) {
            return;
        }

        $websocket = $this->app->make(Websocket::class);

        try {
            $websocket->reset(true)->setSender($fd);
            // trigger 'disconnect' websocket event
            if ($websocket->eventExists('disconnect')) {
                $websocket->call('disconnect');
            } else {
                $this->websocketHandler->onClose($fd, $reactorId);
            }
            // leave all rooms
            $websocket->leave();
        } catch (Throwable $e) {
            $this->logServerError($e);
        }
    }

    /**
     * Indicates if a packet is websocket push action.
     *
     * @param mixed
     *
     * @return bool
     */
    protected function isWebsocketPushPacket($packet)
    {
        if (! is_array($packet)) {
            return false;
        }

        return $this->isServerWebsocket
            && array_key_exists('action', $packet)
            && $packet['action'] === Websocket::PUSH_ACTION;
    }


    /**
     * Push websocket message to clients.
     *
     * @param \Swoole\Websocket\Server $server
     * @param mixed $data
     */
    public function pushMessage($server, array $data)
    {
        $pusher = Pusher::make($data, $server);
        $pusher->push($this->payloadParser->encode(
            $pusher->getEvent(),
            $pusher->getMessage()
        ));
    }

    /**
     * Set frame parser for websocket.
     *
     * @param \SwooleTW\Http\Websocket\Parser $payloadParser
     *
     * @return \SwooleTW\Http\Concerns\InteractsWithWebsocket
     */
    public function setPayloadParser(Parser $payloadParser)
    {
        $this->payloadParser = $payloadParser;

        return $this;
    }

    /**
     * Get frame parser for websocket.
     */
    public function getPayloadParser()
    {
        return $this->payloadParser;
    }

    /**
     * Prepare settings if websocket is enabled.
     */
    protected function prepareWebsocket()
    {
        $config = $this->container->make('config');
        $parser = $config->get('swoole_websocket.parser');

        if (! $this->isServerWebsocket = $config->get('swoole_http.websocket.enabled')) {
            return;
        }

        if ($config->get('swoole_websocket.handshake.enabled')) {
            $this->wsEvents = array_merge($this->wsEvents, ['handshake']);
        }

        $this->events = array_merge($this->events ?? [], $this->wsEvents);
        $this->prepareWebsocketRoom();
        $this->setPayloadParser(new $parser);
    }

    /**
     * Check if it's a websocket fd.
     *
     * @param int $fd
     *
     * @return bool
     */
    protected function isServerWebsocket(int $fd): bool
    {
        return array_key_exists(
            'websocket_status',
            $this->container->make(Server::class)
                ->connection_info($fd)
        );
    }

    /**
     * Prepare websocket handler for onOpen and onClose callback.
     *
     * @throws \Exception
     */
    protected function prepareWebsocketHandler()
    {
        $handlerClass = $this->container->make('config')->get('swoole_websocket.handler');

        if (! $handlerClass) {
            throw new WebsocketNotSetInConfigException;
        }

        $this->setWebsocketHandler($this->app->make($handlerClass));
    }

    /**
     * Prepare websocket room.
     */
    protected function prepareWebsocketRoom()
    {
        $config = $this->container->make('config');
        $driver = $config->get('swoole_websocket.default');
        $websocketConfig = $config->get("swoole_websocket.settings.{$driver}");
        $className = $config->get("swoole_websocket.drivers.{$driver}");

        $this->websocketRoom = new $className($websocketConfig);
        $this->websocketRoom->prepare();
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
     * @param string $class
     * @param array $settings
     *
     * @return \SwooleTW\Http\Websocket\Rooms\RoomContract
     */
    protected function createRoom(string $class, array $settings): RoomContract
    {
        return new $class($settings);
    }

    /**
     * Bind room instance to Laravel app container.
     */
    protected function bindRoom(): void
    {
        $this->app->singleton(RoomContract::class, function () {
            return $this->websocketRoom;
        });

        $this->app->alias(RoomContract::class, 'swoole.room');
    }

    /**
     * Bind websocket instance to Laravel app container.
     */
    protected function bindWebsocket()
    {
        $this->app->singleton(Websocket::class, function (Container $app) {
            return new Websocket($app->make(RoomContract::class), new Pipeline($app));
        });

        $this->app->alias(Websocket::class, 'swoole.websocket');
    }

    /**
     * Load websocket routes file.
     */
    protected function loadWebsocketRoutes()
    {
        $routePath = $this->container->make('config')
            ->get('swoole_websocket.route_file');

        if (! file_exists($routePath)) {
            $routePath = __DIR__ . '/../../routes/websocket.php';
        }

        return require $routePath;
    }

    /**
     * Indicates if the payload is websocket push.
     *
     * @param mixed $payload
     *
     * @return boolean
     */
    public function isWebsocketPushPayload($payload): bool
    {
        if (! is_array($payload)) {
            return false;
        }

        return $this->isServerWebsocket
            && ($payload['action'] ?? null) === Websocket::PUSH_ACTION
            && array_key_exists('data', $payload);
    }
}
