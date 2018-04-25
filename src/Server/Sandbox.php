<?php

namespace SwooleTW\Http\Server;

use Illuminate\Container\Container;
use SwooleTW\Http\Server\Application;
use Illuminate\Support\Facades\Facade;
use Laravel\Lumen\Application as LumenApplication;

class Sandbox
{
    /**
     * @var \SwooleTW\Http\Server\Application
     */
    protected static $application;

    /**
     * @var \SwooleTW\Http\Server\Application
     */
    protected static $snapshot;

    /**
     * @var boolean
     */
    public static $enabled = false;

    /**
     * Set a base application
     *
     * @param \SwooleTW\Http\Server\Application
     */
    public static function setApplication(Application $application)
    {
        static::$application = $application;
    }

    /**
     * Get an application snapshot
     *
     * @return \SwooleTW\Http\Server\Application
     */
    public static function getApplication()
    {
        if (is_null(static::$application)) {
            throw new \RuntimeException('Base application not set yet.');
        }

        if (static::$snapshot instanceOf Application) {
            return static::$snapshot;
        }

        $snapshot = clone static::$application;
        static::resetLaravelApp($snapshot->getApplication());

        return static::$snapshot = $snapshot;
    }

    /**
     * Reset Laravel/Lumen Application.
     */
    protected static function resetLaravelApp($application)
    {
        if (static::$application->getFramework() == 'laravel') {
            $application->bootstrapWith([
                'Illuminate\Foundation\Bootstrap\LoadConfiguration'
            ]);
        } else {
            $reflector = new \ReflectionMethod(LumenApplication::class, 'registerConfigBindings');
            $reflector->setAccessible(true);
            $reflector->invoke($application);
        }
    }

    /**
     * Get a laravel snapshot
     *
     * @return \Illuminate\Container\Container
     */
    public static function getLaravelApp()
    {
        if (static::$snapshot instanceOf Application) {
            return static::$snapshot->getApplication();
        }

        return static::getApplication()->getApplication();
    }

    /**
     * Set laravel snapshot to container and facade.
     */
    public static function enable()
    {
        if (is_null(static::$snapshot)) {
            static::getApplication(static::$application);
        }

        static::setInstance(static::getLaravelApp());
        static::$enabled = true;
    }

    /**
     * Set original laravel app to container and facade.
     */
    public static function disable()
    {
        if (! static::$enabled) {
            return;
        }

        if (static::$snapshot instanceOf Application) {
            static::$snapshot = null;
        }

        static::setInstance(static::$application->getApplication());
    }

    /**
     * Replace app's self bindings.
     */
    protected static function setInstance($application)
    {
        $application->instance('app', $application);
        $application->instance(Container::class, $application);

        if ($application->getFramework() === 'lumen') {
            $application->instance(LumenApplication::class, $application);
        }

        Container::setInstance($application);
        // TODO: only clean necessary facade names
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($application);
    }
}
