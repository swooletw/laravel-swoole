<?php

namespace SwooleTW\Http\Server\Websocket\Formatter;

use SwooleTW\Http\Server\Websocket\Formatter\FormatterContract;

class DefaultFormatter implements FormatterContract
{
    /**
     * Input from emit task.
     */
    public function input($event, $data)
    {
        return [
            'event' => $event,
            'data' => $data
        ];
    }

    /**
     * Output message for websocket push.
     */
    public function output($data)
    {
        return is_array($data) ? json_encode($data) : $data;
    }
}
