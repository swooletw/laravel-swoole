<?php

namespace SwooleTW\Http\Websocket\Formatters;

use SwooleTW\Http\Websocket\Formatters\FormatterContract;

class DefaultFormatter implements FormatterContract
{
    /**
     * Input message on websocket connected.
     * Define and return event name and payload data here.
     *
     * @param \Swoole\Websocket\Frame $frame
     * @return array
     */
    public function input($frame)
    {
        $data = json_decode($frame->data, true);

        return [
            'event' => $data['event'] ?? null,
            'data' => $data['data'] ?? null
        ];
    }

    /**
     * Output message for websocket push.
     *
     * @return mixed
     */
    public function output($event, $data)
    {
        return json_encode([
            'event' => $event,
            'data' => $data
        ]);
    }
}
