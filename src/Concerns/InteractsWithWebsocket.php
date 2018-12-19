<?php

namespace SwooleTW\Http\Concerns;


use Illuminate\Contracts\Container\Container;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Arr;
use SwooleTW\Http\Exceptions\WebsocketNotSetInConfigException;
use SwooleTW\Http\Helpers\Service;
use SwooleTW\Http\Transformers\Request;
use SwooleTW\Http\Websocket\HandlerContract;
use SwooleTW\Http\Websocket\Parser;
use SwooleTW\Http\Websocket\Push;
use SwooleTW\Http\Websocket\Rooms\RoomContract;
use SwooleTW\Http\Websocket\Websocket;
use Throwable;

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
     * Websocket server events.
     *
     * @var array
     */
    protected $wsEvents = ['open', 'message', 'close'];

    /**
     * "onOpen" listener.
     *
     * @param \Swoole\Http\Request $swooleRequest
     */
    public function onOpen($swooleRequest)
    {
        $illuminateRequest = Request::make($swooleRequest)->toIlluminate();
        $websocket = $this->app->make(Service::WEBSOCKET_ALIAS);
        $sandbox = $this->app->make(Service::SANDBOX_ALIAS);

        try {
            $websocket->reset(true)->setSender($swooleRequest->fd);
            // set currnt request to sandbox
            $sandbox->setRequest($illuminateRequest);
            // enable sandbox
            $sandbox->enable();
            // check if socket.io connection established
            if (!$this->websocketHandler->onOpen($swooleRequest->fd, $illuminateRequest)) {
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

        $websocket = $this->app->make(Service::WEBSOCKET_ALIAS);
        $sandbox = $this->app->make(Service::SANDBOX_ALIAS);

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
        if (!$this->isServerWebsocket($fd) || !$server instanceof Websocket) {
            return;
        }

        $websocket = $this->app->make(Service::WEBSOCKET_ALIAS);

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
     * Push websocket message to clients.
     *
     * @param \Swoole\Websocket\Server $server
     * @param mixed $data
     */
    public function pushMessage($server, array $data)
    {
        $push = Push::new($data);
        $payload = $this->payloadParser->encode($push->getEvent(), $push->getMessage());

        // attach sender if not broadcast
        if (!$push->isBroadcast() && $push->getSender() && !$push->hasOwnDescriptor()) {
            $push->addDescriptor($push->getSender());
        }

        // check if to broadcast all clients
        if ($push->isBroadcastToAllDescriptors()) {
            $push->mergeDescriptor($this->filterWebsocket($server->connections));
        }

        // push message to designated fds
        foreach ($push->getDescriptors() as $descriptor) {
            if ($server->exist($descriptor) || !$push->isBroadcastToDescriptor((int)$descriptor)) {
                $server->push($descriptor, $payload, $push->getOpcode());
            }
        }
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
        $config = $this->container->make(Service::CONFIG_ALIAS);
        $isWebsocket = $config->get('swoole_http.websocket.enabled');
        $parser = $config->get('swoole_websocket.parser');

        if ($isWebsocket) {
            $this->types = array_merge($this->types ?? [], $this->wsEvents);
            $this->isServerWebsocket = true;
            $this->setPayloadParser(new $parser);
        }
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
        $info = $this->container->make(Service::SERVER_ALIAS)->connection_info($fd);

        return Arr::has($info, 'websocket_status') && Arr::get($info, 'websocket_status');
    }

    /**
     * Returns all descriptors that are websocket
     *
     * @param array $descriptors
     *
     * @return array
     */
    protected function filterWebsocket(array $descriptors): array
    {
        $callback = function ($descriptor) {
            return $this->isServerWebsocket($descriptor);
        };

        return collect($descriptors)->filter($callback)->toArray();
    }

    /**
     * Prepare websocket handler for onOpen and onClose callback.
     *
     * @throws \Exception
     */
    protected function prepareWebsocketHandler()
    {
        $handlerClass = $this->container->make(Service::CONFIG_ALIAS)->get('swoole_websocket.handler');

        if (!$handlerClass) {
            throw new WebsocketNotSetInConfigException;
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
        $this->app->singleton(RoomContract::class, function (Container $container) {
            $config = $container->make(Service::CONFIG_ALIAS);
            $driver = $config->get('swoole_websocket.default');
            $settings = $config->get("swoole_websocket.settings.{$driver}");
            $className = $config->get("swoole_websocket.drivers.{$driver}");

            return $this->createRoom($className, $settings);
        });

        $this->app->alias(RoomContract::class, 'swoole.room');
    }

    /**
     * Bind websocket instance to Laravel app container.
     */
    protected function bindWebsocket()
    {
        $this->app->singleton(Websocket::class, function (Container $app) {
            return new Websocket($app->make(Service::ROOM_ALIAS), new Pipeline($app));
        });

        $this->app->alias(Websocket::class, Service::WEBSOCKET_ALIAS);
    }

    /**
     * Load websocket routes file.
     */
    protected function loadWebsocketRoutes()
    {
        $routePath = $this->container->make(Service::CONFIG_ALIAS)->get('swoole_websocket.route_file');

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
