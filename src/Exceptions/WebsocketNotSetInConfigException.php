<?php

namespace SwooleTW\Http\Exceptions;

use Exception;

class WebsocketNotSetInConfigException extends Exception
{
    /**
     * WebsocketNotSetInConfigException constructor.
     *
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(int $code = 0, \Throwable $previous = null)
    {
        parent::__construct('Websocket handler is not set in swoole_websocket config', $code, $previous);
    }

}
