<?php

namespace SwooleTW\Http\Server\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static this setFramework($framework)
 * @method static string getFramework()
 * @method static this setBaseApp($app)
 * @method static \Illuminate\Container\Container getBaseApp()
 * @method static \Illuminate\Container\Container getApplication()
 * @method static this setRequest($request)
 * @method static \Illuminate\Http\Request getRequest($request)
 * @method static \Illuminate\Http\Response run()
 * @method static this setSnapshot($snapshot)
 * @method static \Illuminate\Container\Container getSnapshot()
 * @method static this initialize()
 * @method static boolean isLaravel()
 * @method static void terminate($request, $response)
 * @method static void enable()
 * @method static void disable()
 * @method static void setInstance($app)
 *
 * @see \SwooleTW\Http\Server\Sandbox
 */
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