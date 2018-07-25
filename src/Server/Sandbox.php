<?php

namespace SwooleTW\Http\Server;

use Swoole\Coroutine;
use Illuminate\Http\Request;
use Illuminate\Container\Container;
use SwooleTW\Http\Coroutine\Context;
use Illuminate\Contracts\Http\Kernel;
use SwooleTW\Http\Server\Application;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;
use Laravel\Lumen\Application as LumenApplication;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class Sandbox
{
    /**
     * @var \Illuminate\Container\Container
     */
    protected $app;

    /**
     * @var string
     */
    protected $framework = 'laravel';

    /**
     * @var \Illuminate\Config\Repository
     */
    protected $config;

    /**
     * @var array
     */
    protected $providers = [];

    /**
     * Make a sandbox instance.
     *
     * @param \Illuminate\Container\Container
     * @return \SwooleTW\Http\Server\Sandbox
     */
    public static function make(Container $app)
    {
        return new static($app);
    }

    /**
     * Sandbox constructor.
     */
    public function __construct(Container $app)
    {
        $this->setBaseApp($app);
        $this->setInitialConfig();
        $this->setInitialProviders();
    }

    /**
     * Set framework type
     */
    public function setFramework(string $framework)
    {
        $this->framework = $framework;

        return $this;
    }

    /**
     * Set a base application
     *
     * @param \Illuminate\Container\Container
     */
    public function setBaseApp(Container $app)
    {
        $this->app = $app;

        return $this;
    }

    /**
     * Set current request.
     *
     * @param \Illuminate\Http\Request
     */
    public function setRequest(Request $request)
    {
        Context::setData('_request', $request);

        return $this;
    }

    /**
     * Set current snapshot.
     *
     * @param \Illuminate\Container\Container
     */
    public function setSnapshot(Container $snapshot)
    {
        Context::setApp($snapshot);

        return $this;
    }

    /**
     * Set config snapshot.
     */
    protected function setInitialConfig()
    {
        $this->config = clone $this->getBaseApp()->make('config');
    }

    /**
     * Initialize customized service providers.
     */
    protected function setInitialProviders()
    {
        $app = $this->getApplication();
        $providers = $this->config->get('swoole_http.providers', []);

        foreach ($providers as $provider) {
            if (class_exists($provider)) {
                $provider = new $provider($app);
                $this->providers[get_class($provider)] = $provider;
            }
        }
    }

    /**
     * Get base application.
     *
     * @return \Illuminate\Container\Container
     */
    public function getBaseApp()
    {
        return $this->app;
    }

    /**
     * Get an application snapshot
     *
     * @return \Illuminate\Container\Container
     */
    public function getApplication()
    {
        $snapshot = $this->getSnapshot();
        if ($snapshot instanceOf Container) {
            return $snapshot;
        }

        $snapshot = clone $this->getBaseApp();
        $this->setSnapshot($snapshot);

        return $snapshot;
    }

    /**
     * Reset Laravel/Lumen Application.
     */
    protected function resetApp($app)
    {
        $this->resetConfigInstance($app);
        $this->resetSession($app);
        $this->resetCookie($app);
        $this->clearInstances($app);
        $this->bindRequest($app);
        $this->rebindKernelContainer($app);
        $this->rebindRouterContainer($app);
        $this->rebindViewContainer($app);
        $this->resetProviders($app);
    }

    /**
     * Clear resolved instances.
     */
    protected function clearInstances($app)
    {
        $instances = $this->config->get('swoole_http.instances', []);
        foreach ($instances as $instance) {
            $app->forgetInstance($instance);
        }
    }

    /**
     * Bind illuminate request to laravel/lumen application.
     */
    protected function bindRequest($app)
    {
        $request = $this->getRequest();
        if ($request instanceof Request) {
            $app->instance('request', $request);
        }
    }

    /**
     * Re-register and reboot service providers.
     */
    protected function resetProviders($app)
    {
        foreach ($this->providers as $provider) {
            $this->rebindProviderContainer($provider, $app);
            if (method_exists($provider, 'register')) {
                $provider->register();
            }
            if (method_exists($provider, 'boot')) {
                $app->call([$provider, 'boot']);
            }
        }
    }

    /**
     * Rebind service provider's container.
     */
    protected function rebindProviderContainer($provider, $app)
    {
        $closure = function () use ($app) {
            $this->app = $app;
        };

        $resetProvider = $closure->bindTo($provider, $provider);
        $resetProvider();
    }

    /**
     * Reset laravel/lumen's config to initial values.
     */
    protected function resetConfigInstance($app)
    {
        $app->instance('config', clone $this->config);
    }

    /**
     * Reset laravel's session data.
     */
    protected function resetSession($app)
    {
        if (isset($app['session'])) {
            $session = $app->make('session');
            $session->flush();
        }
    }

    /**
     * Reset laravel's cookie.
     */
    protected function resetCookie($app)
    {
        if (isset($app['cookie'])) {
            $cookies = $app->make('cookie');
            foreach ($cookies->getQueuedCookies() as $key => $value) {
                $cookies->unqueue($key);
            }
        }
    }

    /**
     * Rebind laravel's container in router.
     */
    protected function rebindRouterContainer($app)
    {
        if ($this->framework === 'laravel') {
            $router = $app->make('router');
            $request = $this->getRequest();
            $closure = function () use ($app, $request) {
                $this->container = $app;
                if (is_null($request)) {
                    return;
                }
                try {
                    $route = $this->routes->match($request);
                    // clear resolved controller
                    if (property_exists($route, 'container')) {
                        $route->controller = null;
                    }
                    // rebind matched route's container
                    $route->setContainer($app);
                } catch (NotFoundHttpException $e) {
                    // do nothing
                }
            };

            $resetRouter = $closure->bindTo($router, $router);
            $resetRouter();
        } elseif ($this->framework === 'lumen') {
            // lumen router only exists after lumen 5.5
            if (property_exists($app, 'router')) {
                $app->router->app = $app;
            }
        }
    }

    /**
     * Rebind laravel/lumen's container in view.
     */
    protected function rebindViewContainer($app)
    {
        $view = $app->make('view');

        $closure = function () use ($app) {
            $this->container = $app;
            $this->shared['app'] = $app;
        };

        $resetView = $closure->bindTo($view, $view);
        $resetView();
    }

    /**
     * Rebind laravel's container in kernel.
     */
    protected function rebindKernelContainer($app)
    {
        if ($this->framework === 'laravel') {
            $kernel = $app->make(Kernel::class);

            $closure = function () use ($app) {
                $this->app = $app;
            };

            $resetKernel = $closure->bindTo($kernel, $kernel);
            $resetKernel();
        }
    }

    /**
     * Run framework.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function run(Request $request)
    {
        $shouldUseOb = $this->config->get('swoole_http.ob_output', true);

        if ($shouldUseOb) {
            ob_start();
        }

        // handle request with laravel or lumen
        $method = sprintf('run%s', ucfirst($this->framework));
        $response = $this->$method($request);

        // prepare content for ob
        $content = '';
        if ($shouldUseOb) {
            if ($response instanceof BinaryFileResponse) {
                $shouldUseOb = false;
                ob_end_clean();
            } elseif ($isStream = $response instanceof StreamedResponse) {
                $response->sendContent();
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
            if ($isStream) {
                $response->output = ob_get_contents();
            } else {
                $response->setContent(ob_get_contents());
            }
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
     * Get Laravel kernel.
     */
    protected function getKernel()
    {
        return $this->getApplication()->make(Kernel::class);
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
    }

    /**
     * Terminate Lumen framework.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Http\Response $response
     */
    protected function terminateLumen(Request $request, $response)
    {
        $app = $this->getApplication();

        $reflection = new \ReflectionObject($app);

        $middleware = $reflection->getProperty('middleware');
        $middleware->setAccessible(true);

        $callTerminableMiddleware = $reflection->getMethod('callTerminableMiddleware');
        $callTerminableMiddleware->setAccessible(true);

        if (count($middleware->getValue($app)) > 0) {
            $callTerminableMiddleware->invoke($app, $response);
        }
    }

    /**
     * Set laravel snapshot to container and facade.
     */
    public function enable()
    {
        $this->setInstance($app = $this->getApplication());
        $this->resetApp($app);
    }

    /**
     * Set original laravel app to container and facade.
     */
    public function disable()
    {
        Context::clear();
        $this->setInstance($this->getBaseApp());
    }

    /**
     * Replace app's self bindings.
     */
    public function setInstance(Container $app)
    {
        $app->instance('app', $app);
        $app->instance(Container::class, $app);

        if ($this->framework === 'lumen') {
            $app->instance(LumenApplication::class, $app);
        }

        Container::setInstance($app);
        Context::setApp($app);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($app);
    }

    /**
     * Get current snapshot.
     */
    public function getSnapshot()
    {
        return Context::getApp();
    }

    /**
     * Remove current request.
     */
    protected function removeRequest()
    {
        return Context::removeData('_request');
    }

    /**
     * Get current request.
     */
    public function getRequest()
    {
        return Context::getData('_request');
    }
}
