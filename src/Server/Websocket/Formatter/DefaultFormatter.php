<?php

namespace SwooleTW\Http\Server\Websocket\Formatter;

use SwooleTW\Http\Server\Websocket\Formatter\FormatterContract;

class DefaultFormatter implements FormatterContract
{
    /**
     * Output message for websocket push.
     */
    public function output($event, $data)
    {
        return json_encode([
            'event' => $event,
            'data' => $data
        ]);
    }
}
