<?php

namespace SwooleTW\Http\Server\Websocket\Formatter;

interface FormatterContract
{
    /**
     * Outpur for websocket push
     */
    public function output($event, $data);
}
