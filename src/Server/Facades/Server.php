<?php

namespace SwooleTW\Http\Server\Facades;

use Illuminate\Support\Facades\Facade;
use SwooleTW\Http\Helpers\Alias;

/**
 * Class Server
 *
 * @mixin \Swoole\Http\Server
 */
class Server extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Alias::SERVER;
    }
}