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
    protected $application;

    /**
     * @var \SwooleTW\Http\Server\Application
     */
    protected $snapshot;

    /**
     * @var boolean
     */
    public $enabled = false;

    /**
     * Make a sandbox.
     *
     * @param \SwooleTW\Http\Server\Application $application
     * @return \SwooleTW\Http\Server\Sandbox
     */
    public static function make(Application $application)
    {
        return new static($application);
    }

    /**
     * Sandbox constructor.
     *
     * @param \SwooleTW\Http\Server\Application $application
     */
    public function __construct($application)
    {
        $this->application = $application;
    }

    /**
     * Get an application snapshot
     *
     * @return \SwooleTW\Http\Server\Application
     */
    public function getApplication()
    {
        if ($this->snapshot instanceOf Application) {
            return $this->snapshot;
        }

        $snapshot = clone $this->application;
        $this->resetLaravelApp($snapshot->getApplication());

        return $this->snapshot = $snapshot;
    }

    /**
     * Reset Laravel/Lumen Application.
     */
    protected function resetLaravelApp($application)
    {
        if ($this->application->getFramework() == 'laravel') {
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
    public function getLaravelApp()
    {
        if ($this->snapshot instanceOf Application) {
            return $this->snapshot->getApplication();
        }

        return $this->getApplication()->getApplication();
    }

    /**
     * Set laravel snapshot to container and facade.
     */
    public function enable()
    {
        if (! $this->snapshot instanceOf Application) {
            $this->getApplication($this->application);
        }

        $this->setInstance($this->getLaravelApp());
        $this->enabled = true;
    }

    /**
     * Set original laravel app to container and facade.
     */
    public function disable()
    {
        if (! $this->enabled) {
            return;
        }

        if ($this->snapshot instanceOf Application) {
            $this->snapshot = null;
        }

        $this->setInstance($this->application->getApplication());
    }

    /**
     * Replace app's self bindings.
     */
    protected function setInstance($application)
    {
        $application->instance('app', $application);
        $application->instance(Container::class, $application);

        Container::setInstance($application);
        // TODO: only clean necessary facade names
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($application);
    }
}
