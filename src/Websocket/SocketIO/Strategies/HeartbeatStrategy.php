<?php

namespace SwooleTW\Http\Websocket\SocketIO\Strategies;

use SwooleTW\Http\Websocket\SocketIO\Packet;

class HeartbeatStrategy
{
    /**
     * If return value is true will skip decoding.
     *
     * @param \Swoole\WebSocket\Server $server
     * @param \Swoole\WebSocket\Frame $frame
     *
     * @return boolean
     */
    public function handle($server, $frame)
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
