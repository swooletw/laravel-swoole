<?php

namespace SwooleTW\Http\Websocket\SocketIO;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Swoole\Websocket\Frame;
use SwooleTW\Http\Server\Facades\Server;
use SwooleTW\Http\Websocket\HandlerContract;

class WebsocketHandler implements HandlerContract
{
    /**
     * "onOpen" listener.
     *
     * @param int $fd
     * @param \Illuminate\Http\Request $request
     *
     * @return bool
     */
    public function onOpen($fd, Request $request)
    {
        if (! $request->input('sid')) {
            $payload = json_encode(
                [
                    'sid' => base64_encode(uniqid()),
                    'upgrades' => [],
                    'pingInterval' => Config::get('swoole_websocket.ping_interval'),
                    'pingTimeout' => Config::get('swoole_websocket.ping_timeout'),
                ]
            );
            $initPayload = Packet::OPEN . $payload;
            $connectPayload = Packet::MESSAGE . Packet::CONNECT;

            App::make(Server::class)->push($fd, $initPayload);
            App::make(Server::class)->push($fd, $connectPayload);

            return true;
        }

        return false;
    }

    /**
     * "onMessage" listener.
     *  only triggered when event handler not found
     *
     * @param \Swoole\Websocket\Frame $frame
     */
    public function onMessage(Frame $frame)
    {
        return;
    }

    /**
     * "onClose" listener.
     *
     * @param int $fd
     * @param int $reactorId
     */
    public function onClose($fd, $reactorId)
    {
        return;
    }
}
