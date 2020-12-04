<?php

namespace SwooleTW\Http\Concerns;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Facade;
use Illuminate\Contracts\Container\Container;
use SwooleTW\Http\Exceptions\FrameworkNotSupportException;

/**
 * Trait WithApplication
 *
 * @property Container $container
 * @property string $framework
 */
trait WithApplication
{
    /**
     * Laravel|Lumen Application.
     *
     * @var \Illuminate\Contracts\Container\Container|mixed
     */
    protected $app;

    /**
     * Bootstrap framework.
     */
    protected function bootstrap()
    {
        if ($this->framework === 'laravel') {
            $bootstrappers = $this->getBootstrappers();
            $this->app->bootstrapWith($bootstrappers);
        } else {
            // for Lumen 5.7
            // https://github.com/laravel/lumen-framework/commit/42cbc998375718b1a8a11883e033617024e57260#diff-c9248b3167fc44af085b81db2e292837
            if (method_exists($this->app, 'boot')) {
                $this->app->boot();
            }
            $this->app->withFacades();
        }

        $this->preResolveInstances();
    }

    /**
     * Load application.
     *
     * @return \Illuminate\Contracts\Foundation\Application
     */
    protected function loadApplication()
    {
        return require "{$this->basePath}/bootstrap/app.php";
    }

    /**
     * @return \Illuminate\Contracts\Container\Container|mixed
     * @throws \ReflectionException
     */
    public function getApplication()
    {
        if (! $this->app instanceof Container) {
            $this->app = $this->loadApplication();
            $this->bootstrap();
        }

        return $this->app;
    }

    /**
     * Set laravel application.
     *
     * @param \Illuminate\Contracts\Container\Container $app
     */
    public function setApplication(Container $app)
    {
        $this->app = $app;
    }

    /**
     * Set framework.
     *
     * @param string $framework
     *
     * @throws \Exception
     */
    protected function setFramework($framework)
    {
        $framework = strtolower($framework);

        if (! in_array($framework, ['laravel', 'lumen'])) {
            throw new FrameworkNotSupportException($framework);
        }

        $this->framework = $framework;
    }

    /**
     * Get framework.
     */
    public function getFramework()
    {
        return $this->framework;
    }

    /**
     * Set base path.
     *
     * @param string $basePath
     */
    protected function setBasePath($basePath)
    {
        $this->basePath = is_null($basePath) ? base_path() : $basePath;
    }

    /**
     * Get basepath.
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * Reslove some instances before request.
     *
     * @throws \ReflectionException
     */
    protected function preResolveInstances()
    {
        $resolves = $this->container->make('config')
            ->get('swoole_http.pre_resolved', []);

        foreach ($resolves as $abstract) {
            if ($this->getApplication()->offsetExists($abstract)) {
                $this->getApplication()->make($abstract);
            }
        }
    }

    /**
     * Get bootstrappers.
     *
     * @return array
     * @throws \ReflectionException
     */
    protected function getBootstrappers()
    {
        $kernel = $this->getApplication()->make(Kernel::class);

        $reflection = new \ReflectionObject($kernel);
        $bootstrappersMethod = $reflection->getMethod('bootstrappers');
        $bootstrappersMethod->setAccessible(true);
        $bootstrappers = $bootstrappersMethod->invoke($kernel);

        array_splice($bootstrappers, -2, 0, ['Illuminate\Foundation\Bootstrap\SetRequestForConsole']);

        return $bootstrappers;
    }
}
