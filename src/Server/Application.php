<?php

namespace SwooleTW\Http\Server;

use Illuminate\Http\Request;
use Illuminate\Container\Container;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

use Laravel\Lumen\Application as LumenApplication;

class Application
{
    /**
     * Current framework.
     *
     * @var string
     */
    protected $framework;

    /**
     * The framework base path.
     *
     * @var string
     */
    protected $basePath;

    /**
     * Laravel|Lumen Application.
     *
     * @var \Illuminate\Container\Container
     */
    protected $application;

    /**
     * @var \Illuminate\Contracts\Http\Kernel
     */
    protected $kernel;

    /**
     * Service providers to be reset.
     *
     * @var array
     */
    protected $providers = [];

    /**
     * Instance names to be reset.
     *
     * @var array
     */
    protected $instances = [];

    /**
     * Resolved facades to be reset.
     *
     * @var array
     */
    protected $facades = [];

    /**
     * Aliases for pre-resolving.
     *
     * @var array
     */
    protected $resolves = [
        'view', 'files', 'session', 'session.store', 'routes',
        'db', 'db.factory', 'cache', 'cache.store', 'config', 'cookie',
        'encrypter', 'hash', 'router', 'translator', 'url', 'log'
    ];

    /**
     * Make an application.
     *
     * @param string $framework
     * @param string $basePath
     * @return \SwooleTW\Http\Server\Application
     */
    public static function make($framework, $basePath = null)
    {
        return new static($framework, $basePath);
    }

    /**
     * Application constructor.
     *
     * @param string $framework
     * @param string $basePath
     */
    public function __construct($framework, $basePath = null)
    {
        $this->setFramework($framework);
        $this->setBasePath($basePath);

        $this->bootstrap();
        $this->initProviders();
        $this->initFacades();
        $this->initInstances();
    }

    /**
     * Bootstrap framework.
     */
    protected function bootstrap()
    {
        $application = $this->getApplication();

        if ($this->framework === 'laravel') {
            $bootstrappers = $this->getBootstrappers();
            $application->bootstrapWith($bootstrappers);
        } elseif (is_null(Facade::getFacadeApplication())) {
            $application->withFacades();
        }

        $this->preResolveInstances($application);
    }

    /**
     * Initialize customized service providers.
     */
    protected function initProviders()
    {
        $app = $this->getApplication();
        $providers = $app['config']->get('swoole_http.providers', []);

        foreach ($providers as $provider) {
            if (! $provider instanceof ServiceProvider) {
                $provider = new $provider($app);
            }
            $this->providers[get_class($provider)] = $provider;
        }
    }

    /**
     * Initialize customized instances.
     */
    protected function initInstances()
    {
        $app = $this->getApplication();
        $instances = $app['config']->get('swoole_http.instances', []);

        $this->instances = array_filter($instances, function ($value) {
            return is_string($value);
        });
    }

    /**
     * Initialize customized facades.
     */
    protected function initFacades()
    {
        $app = $this->getApplication();
        $facades = $app['config']->get('swoole_http.facades', []);

        $this->facades = array_filter($facades, function ($value) {
            return is_string($value);
        });
    }

    /**
     * Re-register and reboot service providers.
     */
    public function resetProviders()
    {
        foreach ($this->providers as $provider) {
            if (method_exists($provider, 'register')) {
                $provider->register();
            }

            if (method_exists($provider, 'boot')) {
                $this->getApplication()->call([$provider, 'boot']);
            }
        }
    }

    /**
     * Clear resolved facades.
     */
    public function clearFacades()
    {
        foreach ($this->facades as $facade) {
            Facade::clearResolvedInstance($facade);
        }
    }

    /**
     * Clear resolved instances.
     */
    public function clearInstances()
    {
        foreach ($this->instances as $instance) {
            $this->getApplication()->forgetInstance($instance);
        }
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
     * @return \Illuminate\Container\Container
     */
    public function getApplication()
    {
        if (! $this->application instanceof Container) {
            $this->application = $this->loadApplication();
        }

        return $this->application;
    }

    /**
     * @return \Illuminate\Contracts\Http\Kernel
     */
    public function getKernel()
    {
        if (! $this->kernel instanceof Kernel) {
            $this->kernel = $this->getApplication()->make(Kernel::class);
        }

        return $this->kernel;
    }

    /**
     * Get application framework.
     */
    public function getFramework()
    {
        return $this->framework;
    }

    /**
     * Run framework.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function run(Request $request)
    {
        $shouldUseOb = $this->application['config']->get('swoole_http.ob_output', true);

        if ($shouldUseOb) {
            ob_start();
        }

        // handle request with laravel or lumen
        $method = sprintf('run%s', ucfirst($this->framework));
        $response = $this->$method($request);

        // prepare content for ob
        $content = '';
        if ($shouldUseOb) {
            if ($response instanceof StreamedResponse ||
                $response instanceof BinaryFileResponse) {
                $shouldUseOb = false;
            } elseif ($response instanceof SymfonyResponse) {
                $content = $response->getContent();
            } else {
                $content = (string) $response;
            }
        }

        // process terminating logics
        $this->terminate($request, $response);

        // set ob content to response
        if ($shouldUseOb && strlen($content) === 0 && ob_get_length() > 0) {
            $response->setContent(ob_get_contents());
        }

        if ($shouldUseOb) {
            ob_end_clean();
        }

        return $response;
    }

    /**
     * Run Laravel framework.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    protected function runLaravel(Request $request)
    {
        return $this->getKernel()->handle($request);
    }

    /**
     * Run lumen framework.
     *
     * @param \Illuminate\Http\Request $request
     * @return mixed
     */
    protected function runLumen(Request $request)
    {
        return $this->getApplication()->dispatch($request);
    }

    /**
     * Get bootstrappers.
     *
     * @return array
     */
    protected function getBootstrappers()
    {
        $kernel = $this->getKernel();

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
            throw new \Exception(sprintf('Not support framework "%s".', $this->framework));
        }

        $this->framework = $framework;
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
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Http\Response $response
     */
    public function terminate(Request $request, $response)
    {
        $method = sprintf('terminate%s', ucfirst($this->framework));

        $this->$method($request, $response);
    }

    /**
     * Terminate Laravel framework.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Http\Response $response
     */
    protected function terminateLaravel(Request $request, $response)
    {
        $this->getKernel()->terminate($request, $response);

        // clean laravel session
        if ($request->hasSession()) {
            $session = $request->getSession();
            $session->flush();
        }

        // clean laravel cookie queue
        if (isset($this->application['cookie'])) {
            $cookies = $this->application['cookie'];
            foreach ($cookies->getQueuedCookies() as $name => $cookie) {
                $cookies->unqueue($name);
            }
        }
    }

    /**
     * Terminate Lumen framework.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Http\Response $response
     */
    protected function terminateLumen(Request $request, $response)
    {
        $application = $this->getApplication();

        $reflection = new \ReflectionObject($application);

        $middleware = $reflection->getProperty('middleware');
        $middleware->setAccessible(true);

        $callTerminableMiddleware = $reflection->getMethod('callTerminableMiddleware');
        $callTerminableMiddleware->setAccessible(true);

        if (count($middleware->getValue($application)) > 0) {
            $callTerminableMiddleware->invoke($application, $response);
        }
    }

    /**
     * Reslove some instances before request.
     */
    protected function preResolveInstances($application)
    {
        foreach ($this->resolves as $abstract) {
            if ($application->offsetExists($abstract)) {
                $application->make($abstract);
            }
        }
    }

    /**
     * Rebind laravel's container in kernel.
     */
    protected function rebindKernelContainer($kernel)
    {
        $application = $this->application;

        $closure = function () use ($application) {
            $this->app = $application;
        };

        $resetKernel = $closure->bindTo($kernel, $kernel);
        $resetKernel();
    }

    /**
     * Clone laravel app and kernel while being cloned.
     */
    public function __clone()
    {
        $application = clone $this->application;

        $this->application = $application;

        if ($this->framework === 'laravel') {
            $this->rebindKernelContainer($this->getKernel());
        }
    }
}
