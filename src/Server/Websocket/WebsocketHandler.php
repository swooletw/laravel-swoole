<?php

namespace SwooleTW\Http\Server\Websocket;

use Swoole\Websocket\Frame;
use Illuminate\Http\Request;
use SwooleTW\Http\Server\Websocket\HandlerContract;

class WebsocketHandler implements HandlerContract
{
    /**
     * "onOpen" listener.
     *
     * @param int $fd
     * @param \Illuminate\Http\Request $request
     */
    public function onOpen($fd, Request $request)
    {
        app('events')->fire('swoole.onOpen', compact('fd', 'request'));
    }

    /**
     * "onMessage" listener.
     *  only triggered when event handler not found
     *
     * @param \Swoole\Websocket\Frame $frame
     */
    public function onMessage(Frame $frame)
    {
        app('events')->fire('swoole.onMessage', compact('frame'));
    }

    /**
     * "onClose" listener.
     *
     * @param int $fd
     * @param int $reactorId
     */
    public function onClose($fd, $reactorId)
    {
        app('events')->fire('swoole.onClose', compact('fd', 'reactorId'));
    }
}
