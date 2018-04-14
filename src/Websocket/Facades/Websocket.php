<?php

namespace SwooleTW\Http\Websocket\Facades;

use Illuminate\Support\Facades\Facade;

class Websocket extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'swoole.websocket';
    }
}