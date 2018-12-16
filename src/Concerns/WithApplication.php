<?php

namespace SwooleTW\Http\Concerns;


use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Facade;

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
     *
     * @throws \ReflectionException
     */
    protected function bootstrap()
    {
        if ($this->framework === 'laravel') {
            $bootstrappers = $this->getBootstrappers();
            $this->app->bootstrapWith($bootstrappers);
        } else if (is_null(Facade::getFacadeApplication())) {
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
        if (!$this->app instanceof Container) {
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

        if (!in_array($framework, ['laravel', 'lumen'])) {
            throw new \Exception(sprintf('Not support framework "%s".', $framework));
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
        $resolves = $this->container->make('config')->get('swoole_http.pre_resolved', []);

        foreach ($resolves as $abstract) {
            if ($this->getApplication()->offsetExists($abstract)) {
                $this->getApplication()->make($abstract);
            }
        }
    }
}
