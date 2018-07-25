<?php

namespace SwooleTW\Http\Server;

use Illuminate\Http\Request;
use SwooleTW\Http\Coroutine\Context;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;
use SwooleTW\Http\Concerns\ResetApplication;
use Illuminate\Contracts\Container\Container;
use Laravel\Lumen\Application as LumenApplication;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class Sandbox
{
    use ResetApplication;

    /**
     * @var \Illuminate\Contracts\Container\Container
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
     * @param \Illuminate\Contracts\Container\Container
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
     * @param \Illuminate\Contracts\Container\Container
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
     * @param \Illuminate\Contracts\Container\Container
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
        $app = $this->getBaseApp();
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
     * @return \Illuminate\Contracts\Container\Container
     */
    public function getBaseApp()
    {
        return $this->app;
    }

    /**
     * Get an application snapshot
     *
     * @return \Illuminate\Contracts\Container\Container
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
     * Run framework.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function run(Request $request)
    {
        $shouldUseOb = $this->config->get('swoole_http.ob_output', true);

        if ($shouldUseOb) {
            return $this->prepareObResponse($request);
        }

        return $this->prepareResponse($request);
    }

    /**
     * Handle request for non-ob case.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    protected function prepareResponse(Request $request)
    {
        // handle request with laravel or lumen
        $response = $this->handleRequest($request);

        // process terminating logics
        $this->terminate($request, $response);

        return $response;
    }

    /**
     * Handle request for ob output.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    protected function prepareObResponse(Request $request)
    {
        ob_start();

        // handle request with laravel or lumen
        $response = $this->handleRequest($request);

        // prepare content for ob
        $content = '';
        if ($isStream = $response instanceof StreamedResponse) {
            $response->sendContent();
        } elseif ($response instanceof SymfonyResponse) {
            $content = $response->getContent();
        } elseif (! $response instanceof BinaryFileResponse) {
            $content = (string) $response;
        }

        // process terminating logics
        $this->terminate($request, $response);

        // append ob content to response
        if (ob_get_length() > 0) {
            if ($isStream) {
                $response->output = ob_get_contents();
            } else {
                $response->setContent(ob_get_contents() . $content);
            }
        }

        ob_end_clean();

        return $response;
    }

    /**
     * Handle request through Laravel or Lumen.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    protected function handleRequest(Request $request)
    {
        if ($this->isLaravel()) {
            return $this->getKernel()->handle($request);
        }

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
     * Return if it's Laravel app.
     */
    protected function isLaravel()
    {
        return $this->framework === 'laravel';
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Http\Response $response
     */
    public function terminate(Request $request, $response)
    {
        if ($this->isLaravel()) {
            $this->getKernel()->terminate($request, $response);
        } else {
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
