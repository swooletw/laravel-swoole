<?php

namespace SwooleTW\Http\Server\Traits;

use SwooleTW\Http\Server\Request;
use SwooleTW\Http\Server\Websocket;

trait CanWebsocket
{
    /**
     * @var string
     */
    protected $roomNamespace = '\\SwooleTW\Http\Server\Room\\';

    /**
     * @var boolean
     */
    protected $isWebsocket = false;

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
        $this->container['events']->fire('swoole.onOpen', $swooleRequest);
    }

    /**
     * "onMessage" listener.
     *
     * @param \Swoole\Websocket\Server $server
     * @param \Swoole\Websocket\Frame $frame
     */
    public function onMessage($server, $frame)
    {
        $this->container['events']->fire('swoole.onMessage', $frame);
    }

    /**
     * "onClose" listener.
     *
     * @param \Swoole\Websocket\Server $server
     * @param int $fd
     */
    public function onClose($server, $fd)
    {
        $info = $server->connection_info($fd);
        if (array_key_exists('websocket_status', $info) && $info['websocket_status']) {
            $this->container['events']->fire('swoole.onClose', $fd);
        }
    }

    /**
     * Prepare websocket and room instances.
     */
    protected function setWebsocket()
    {
        $driver = $this->container['config']->get('swoole_websocket.default');
        $configs = $this->container['config']->get("swoole_websocket.drivers.{$driver}");
        $className = $this->roomNamespace . ucfirst($driver) . 'Room';

        $room = new $className($configs);
        call_user_func([$room, 'prepare']);

        $this->websocket = new Websocket($room);
    }
}
