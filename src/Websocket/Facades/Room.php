<?php

namespace SwooleTW\Http\Websocket\Facades;

use Illuminate\Support\Facades\Facade;
use SwooleTW\Http\Helpers\Alias;

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
        return Alias::ROOM;
    }
}