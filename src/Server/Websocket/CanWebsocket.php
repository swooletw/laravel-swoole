<?php

namespace SwooleTW\Http\Server\Websocket;

use Exception;
use Swoole\Websocket\Frame;
use Swoole\Websocket\Server;
use SwooleTW\Http\Server\Request;
use SwooleTW\Http\Server\Websocket\Websocket;
use SwooleTW\Http\Server\Websocket\HandlerContract;
use SwooleTW\Http\Server\Websocket\Rooms\RoomContract;
use SwooleTW\Http\Server\Websocket\Formatters\FormatterContract;

trait CanWebsocket
{
    /**
     * @var boolean
     */
    protected $isWebsocket = false;

    /**
     * @var SwooleTW\Http\Server\Websocket\HandlerContract
     */
    protected $websocketHandler;

    /**
     * @var SwooleTW\Http\Server\Websocket\Rooms\RoomContract
     */
    protected $websocketRoom;

    /**
     * @var SwooleTW\Http\Server\Websocket\Formatters\FormatterContract
     */
    protected $formatter;

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
            $this->websocketHandler->onOpen($swooleRequest->fd, $illuminateRequest);
        } catch (Exception $e) {
            $this->logServerError($e);
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
        $this->app['swoole.websocket']->setSender($frame->fd);

        $payload = $this->formatter->input($frame);
        $handler = $this->container['config']->get("swoole_websocket.handlers.{$payload['event']}");

        try {
            if ($handler) {
                $this->app->call($handler, [$frame->fd, $payload['data']]);
            } else {
                $this->websocketHandler->onMessage($frame);
            }
        } catch (Exception $e) {
            $this->logServerError($e);
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
            $this->websocketHandler->onClose($fd, $reactorId);
        } catch (Exception $e) {
            $this->logServerError($e);
        }

        $this->app['swoole.websocket']->setSender($fd)->leaveAll();
    }

    /**
     * Push websocket message to clients.
     *
     * @param \Swoole\Websocket\Server $server
     * @param mixed $data
     */
    public function pushMessage(Server $server, array $data)
    {
        $opcode = $data['opcode'] ?? 1;
        $sender = $data['sender'] ?? 0;
        $fds = $data['fds'] ?? [];
        $broadcast = $data['broadcast'] ?? false;
        $event = $data['event'] ?? null;
        $message = $this->formatter->output($event, $data['message']);

        // attach sender if not broadcast
        if (! $broadcast && ! in_array($sender, $fds)) {
            $fds[] = $sender;
        }

        // check if to broadcast all clients
        if ($broadcast && empty($fds)) {
            foreach ($server->connections as $fd) {
                if ($this->isWebsocket($fd)) {
                    $fds[] = $fd;
                }
            }
        }

        foreach ($fds as $fd) {
            if ($broadcast && $sender === (integer) $fd) {
                continue;
            }
            $server->push($fd, $message, $opcode);
        }
    }

    /**
     * Set message formatter for websocket.
     *
     * @param \SwooleTW\Http\Server\Websocket\Formatter\FormatterContract $formatter
     */
    public function setFormatter(FormatterContract $formatter)
    {
        $this->formatter = $formatter;

        return $this;
    }

    /**
     * Get message formatter for websocket.
     */
    public function getFormatter()
    {
        return $this->formatter;
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
    protected function setWebsocketRoom()
    {
        $driver = $this->container['config']->get('swoole_websocket.default');
        $configs = $this->container['config']->get("swoole_websocket.settings.{$driver}");
        $className = $this->container['config']->get("swoole_websocket.drivers.{$driver}");

        $this->websocketRoom = new $className($configs);
        $this->websocketRoom->prepare();
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

        $handler = $this->container->make($handlerClass);

        if (! $handler instanceof HandlerContract) {
            throw new Exception(sprintf('%s must implement %s', get_class($handler), HandlerContract::class));
        }

        $this->websocketHandler = $handler;
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
            return new Websocket($this->websocketRoom);
        });
        $this->app->alias(Websocket::class, 'swoole.websocket');
    }
}
