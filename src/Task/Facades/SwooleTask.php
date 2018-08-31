<?php

namespace SwooleTW\Http\Task\Facades;

use Illuminate\Support\Facades\Facade;

class SwooleTask extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'swoole.task';
    }
}
