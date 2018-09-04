<?php

namespace SwooleTW\Http\Concerns;

use Illuminate\Http\Request;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Facade;
use Illuminate\Contracts\Container\Container;
use Laravel\Lumen\Application as LumenApplication;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

trait WithApplication
{
    /**
     * Laravel|Lumen Application.
     *
     * @var \Illuminate\Contracts\Container\Container
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
        } elseif (is_null(Facade::getFacadeApplication())) {
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
        return require $this->basePath . '/bootstrap/app.php';
    }

    /**
     * @return \Illuminate\Contracts\Container\Container
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
     */
    public function setApplication(Container $app)
    {
        $this->app = $app;
    }

    /**
     * Get bootstrappers.
     *
     * @return array
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
     * @throws \Exception
     */
    protected function setFramework($framework)
    {
        $framework = strtolower($framework);

        if (! in_array($framework, ['laravel', 'lumen'])) {
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
     */
    protected function preResolveInstances()
    {
        $resolves = $this->container['config']->get('swoole_http.pre_resolved', []);

        foreach ($resolves as $abstract) {
            if ($this->getApplication()->offsetExists($abstract)) {
                $this->getApplication()->make($abstract);
            }
        }
    }
}
