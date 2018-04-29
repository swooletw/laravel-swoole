<?php

namespace SwooleTW\Http\Websocket\SocketIO\Strategies;

use Swoole\Websocket\Frame;
use Swoole\Websocket\Server;
use SwooleTW\Http\Websocket\SocketIO\Packet;

class HeartbeatStrategy
{
    /**
     * If return value is true will skip decoding.
     *
     * @return boolean
     */
    public function handle(Server $server, Frame $frame)
    {
        $packet = $frame->data;
        $packetLength = strlen($packet);
        $payload = '';

        if (Packet::getPayload($packet)) {
            return false;
        }

        if ($isPing = Packet::isSocketType($packet, 'ping')) {
            $payload .= Packet::PONG;
        }

        if ($isPing && $packetLength > 1) {
            $payload .= substr($packet, 1, $packetLength - 1);
        }

        if ($isPing) {
            $server->push($frame->fd, $payload);
        }

        return true;
    }
}
