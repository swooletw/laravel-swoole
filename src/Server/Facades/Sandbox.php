<?php

namespace SwooleTW\Http\Server\Facades;

use Illuminate\Support\Facades\Facade;

class Sandbox extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'swoole.sandbox';
    }
}