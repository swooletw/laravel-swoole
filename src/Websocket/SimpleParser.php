<?php

namespace SwooleTW\Http\Websocket;

class SimpleParser extends Parser
{
    /**
     * Strategy classes need to implement handle method.
     */
    protected $strategies = [];

    /**
     * Encode output payload for websocket push.
     *
     * @param string $event
     * @param mixed $data
     *
     * @return mixed
     */
    public function encode(string $event, $data)
    {
        return json_encode(
            [
                'event' => $event,
                'data' => $data,
            ]
        );
    }

    /**
     * Input message on websocket connected.
     * Define and return event name and payload data here.
     *
     * @param \Swoole\Websocket\Frame $frame
     *
     * @return array
     */
    public function decode($frame)
    {
        $data = json_decode($frame->data, true);

        return [
            'event' => $data['event'] ?? null,
            'data' => $data['data'] ?? null,
        ];
    }
}
