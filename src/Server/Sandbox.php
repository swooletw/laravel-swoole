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
     * @var \Illuminate\Config\Repository
     */
    protected $config;

    /**
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * @var boolean
     */
    public $enabled = false;

    /**
     * Make a sandbox.
     *
     * @param \SwooleTW\Http\Server\Application
     * @return \SwooleTW\Http\Server\Sandbox
     */
    public static function make(Application $application)
    {
        return new static($application);
    }

    /**
     * Sandbox constructor.
     */
    public function __construct(Application $application)
    {
        $this->setApplication($application);
        $this->setInitialConfig($application);
    }

    /**
     * Set a base application
     *
     * @param \SwooleTW\Http\Server\Application
     */
    public function setApplication(Application $application)
    {
        $this->application = $application;
    }

    /**
     * Set current request.
     *
     * @param \Illuminate\Http\Request
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Set config snapshot.
     *
     * @param \SwooleTW\Http\Server\Application
     */
    protected function setInitialConfig(Application $application)
    {
        $this->config = clone $application->getApplication()['config'];
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
        $this->resetConfigInstance($application);
        $this->clearInstances($application);
        $this->rebindRouterContainer($application);
        $this->rebindViewContainer($application);
    }

    /**
     * Clear resolved instances.
     */
    protected function clearInstances($application)
    {
        $instances = $this->config->get('swoole_http.instances', []);
        foreach ($instances as $instance) {
            $application->forgetInstance($instance);
        }
    }

    /**
     * Reset laravel/lumen's config to initial values.
     */
    protected function resetConfigInstance($application)
    {
        $application->instance('config', clone $this->config);
    }

    /**
     * Rebind laravel's container in router.
     */
    protected function rebindRouterContainer($application)
    {
        if ($this->isFramework('laravel')) {
            $router = $application->make('router');
            $closure = function () use ($application, $request) {
                $this->container = $application;
                if (is_null($request)) {
                    return;
                }
                $route = $this->routes->match($request);
                // clear resolved controller
                if (property_exists($route, 'container')) {
                    $route->controller = null;
                }
                // rebind matched route's container
                $route->setContainer($application);
            };

            $resetRouter = $closure->bindTo($router, $router);
            $resetRouter();
        } elseif ($this->isFramework('lumen')) {
            // lumen router only exists after lumen 5.5
            if (property_exists($application, 'router')) {
                $application->router->app = $application;
            }
        }
    }

    /**
     * Rebind laravel/lumen's container in view.
     */
    protected function rebindViewContainer($application)
    {
        $view = $application->make('view');

        $closure = function () use ($application) {
            $this->container = $application;
            $this->shared['app'] = $application;
        };

        $resetView = $closure->bindTo($view, $view);
        $resetView();
    }

    /**
     * Get application's framework.
     */
    protected function isFramework(string $name)
    {
        return $this->application->getFramework() === $name;
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
        if (is_null($this->snapshot)) {
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

        $this->request = null;

        $this->setInstance($this->application->getApplication());
    }

    /**
     * Replace app's self bindings.
     */
    protected function setInstance($application)
    {
        $application->instance('app', $application);
        $application->instance(Container::class, $application);

        if ($this->isFramework('lumen')) {
            $application->instance(LumenApplication::class, $application);
        }

        Container::setInstance($application);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($application);
    }
}
