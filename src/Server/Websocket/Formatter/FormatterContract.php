<?php

namespace SwooleTW\Http\Server\Websocket\Formatter;

interface FormatterContract
{
    /**
     * Input from emit task
     */
    public function input($event, $data);

    /**
     * Outpur for websocket push
     */
    public function output($data);
}
