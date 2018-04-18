<?php

namespace SwooleTW\Http\Websocket;

use Swoole\Websocket\Frame;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use SwooleTW\Http\Websocket\HandlerContract;

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
        if (! $request->input('sid')) {
            $payload = json_encode([
                'sid' => uniqid(),
                'upgrades' => [],
                'pingInterval' => Config::get('swoole_websocket.ping_interval'),
                'pingTimeout' => Config::get('swoole_websocket.ping_timeout')
            ]);
            $payload = Packet::OPEN . $payload;

            app('swoole.server')->push($fd, $payload);
            app('swoole.server')->push($fd, Packet::MESSAGE . Packet::CONNECT);
        }
    }

    /**
     * "onMessage" listener.
     *  only triggered when event handler not found
     *
     * @param \Swoole\Websocket\Frame $frame
     */
    public function onMessage(Frame $frame)
    {
        //
    }

    /**
     * "onClose" listener.
     *
     * @param int $fd
     * @param int $reactorId
     */
    public function onClose($fd, $reactorId)
    {
        //
    }
}
