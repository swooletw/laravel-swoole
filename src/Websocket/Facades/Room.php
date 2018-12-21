<?php

namespace SwooleTW\Http\Websocket\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Room
 */
class Room extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'swoole.room';
    }
}