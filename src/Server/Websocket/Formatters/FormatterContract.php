<?php

namespace SwooleTW\Http\Server\Websocket\Formatter;

interface FormatterContract
{
    /**
     * Input message on websocket connected.
     * Define and return event name here.
     *
     * @param \Swoole\Websocket\Frame $frame
     * @return string
     */
    public function input($frame);

    /**
     * Outpur for websocket push
     * @return mixed
     */
    public function output($event, $data);
}
