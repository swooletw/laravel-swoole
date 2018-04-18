<?php

namespace SwooleTW\Http\Websocket\SocketIO;

use Swoole\Websocket\Frame;
use SwooleTW\Http\Websocket\Parser;
use SwooleTW\Http\Websocket\SocketIO\Packet;
use SwooleTW\Http\Websocket\SocketIO\Strategies\HeartbeatStrategy;

class SocketIOParser extends Parser
{
    /**
     * Strategy classes need to implement handle method.
     */
    protected $strategies = [
        HeartbeatStrategy::class
    ];

    /**
     * Encode output message for websocket push.
     *
     * @return mixed
     */
    public function encode($event, $data)
    {
        $packet = Packet::MESSAGE . Packet::EVENT;
        $shouldEncode = is_array($data) || is_object($data);
        $data = $shouldEncode ? json_encode($data) : $data;
        $format = $shouldEncode ? '["%s",%s]' : '["%s","%s"]';

        return $packet . sprintf($format, $event, $data);
    }

    /**
     * Decode message from websocket client.
     * Define and return payload here.
     *
     * @param \Swoole\Websocket\Frame $frame
     * @return array
     */
    public function decode(Frame $frame)
    {
        $payload = Packet::getPayload($frame->data);
        $data = json_decode($payload['data'], true);

        return [
            'event' => $payload['event'] ?? null,
            'data' => $data ?: $payload['data']
        ];
    }
}
