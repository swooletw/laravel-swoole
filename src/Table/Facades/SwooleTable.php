<?php

namespace SwooleTW\Http\Table\Facades;

use Illuminate\Support\Facades\Facade;

class SwooleTable extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'swoole.table';
    }
}