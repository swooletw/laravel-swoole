<?php

namespace SwooleTW\Http\Server\Traits;

use Swoole\WebSocket\Server;
use Swoole\WebSocket\Frame;

trait CanWebsocket
{
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
        $this->container['events']->fire('swoole.onOpen', func_get_args());
    }

    /**
     * "onMessage" listener.
     *
     * @param \Swoole\Websocket\Server $server
     * @param \Swoole\Websocket\Frame $frame
     */
    public function onMessage($server, $frame)
    {
        $this->container['events']->fire('swoole.onMessage', func_get_args());
    }

    /**
     * "onClose" listener.
     *
     * @param \Swoole\Websocket\Server $server
     * @param int $fd
     */
    public function onClose($server, $fd)
    {
        $this->container['events']->fire('swoole.onClose', func_get_args());
    }
}
