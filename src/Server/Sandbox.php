<?php

namespace SwooleTW\Http\Server;


use Illuminate\Container\Container as ContainerFacade;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Laravel\Lumen\Application as LumenApplication;
use SwooleTW\Http\Concerns\ResetApplication;
use SwooleTW\Http\Coroutine\Context;
use SwooleTW\Http\Exceptions\SandboxException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class Sandbox
 */
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
     * Constructor
     *
     * @param null $app
     * @param null $framework
     *
     * @throws \SwooleTW\Http\Exceptions\SandboxException
     */
    public function __construct($app = null, $framework = null)
    {
        if (!$app instanceof Container) {
            return;
        }

        $this->setBaseApp($app);
        $this->setFramework($framework ?: $this->framework);
        $this->initialize();
    }

    /**
     * Set framework type.
     *
     * @param string $framework
     *
     * @return \SwooleTW\Http\Server\Sandbox
     */
    public function setFramework(string $framework)
    {
        $this->framework = $framework;

        return $this;
    }

    /**
     * Get framework type.
     */
    public function getFramework()
    {
        return $this->framework;
    }

    /**
     * Set a base application.
     *
     * @param \Illuminate\Contracts\Container\Container $app
     *
     * @return \SwooleTW\Http\Server\Sandbox
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
     *
     * @return \SwooleTW\Http\Server\Sandbox
     */
    public function setRequest(Request $request)
    {
        Context::setData('_request', $request);

        return $this;
    }

    /**
     * Set current snapshot.
     *
     * @param \Illuminate\Contracts\Container\Container $snapshot
     *
     * @return \SwooleTW\Http\Server\Sandbox
     */
    public function setSnapshot(Container $snapshot)
    {
        Context::setApp($snapshot);

        return $this;
    }

    /**
     * Initialize based on base app.
     *
     * @throws \SwooleTW\Http\Exceptions\SandboxException
     */
    public function initialize()
    {
        if (!$this->app instanceof Container) {
            throw new SandboxException('A base app has not been set.');
        }

        $this->setInitialConfig();
        $this->setInitialProviders();
        $this->setInitialResetters();

        return $this;
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
     * @return \Illuminate\Contracts\Container\Container|mixed
     */
    public function getApplication()
    {
        $snapshot = $this->getSnapshot();
        if ($snapshot instanceof Container) {
            return $snapshot;
        }

        $snapshot = clone $this->getBaseApp();
        $this->setSnapshot($snapshot);

        return $snapshot;
    }

    /**
     * Run framework.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     * @throws \SwooleTW\Http\Exceptions\SandboxException
     * @throws \ReflectionException
     */
    public function run(Request $request)
    {
        if (!$this->getSnapshot() instanceof Container) {
            throw new SandboxException('Sandbox is not enabled.');
        }

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
     *
     * @return \Illuminate\Http\Response
     * @throws \ReflectionException
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
     *
     * @return \Illuminate\Http\Response
     * @throws \ReflectionException
     */
    protected function prepareObResponse(Request $request)
    {
        ob_start();

        // handle request with laravel or lumen
        $response = $this->handleRequest($request);

        // prepare content for ob
        $content = '';
        $isFile = false;
        if ($isStream = $response instanceof StreamedResponse) {
            $response->sendContent();
        } else if ($response instanceof SymfonyResponse) {
            $content = $response->getContent();
        } else if (!$isFile = $response instanceof BinaryFileResponse) {
            $content = (string)$response;
        }

        // process terminating logics
        $this->terminate($request, $response);

        // append ob content to response
        if (!$isFile && ob_get_length() > 0) {
            if ($isStream) {
                // TODO add output to StreamedResponse to avoid 'Field declared dynamically'
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
     *
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
    public function isLaravel()
    {
        return $this->framework === 'laravel';
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Http\Response $response
     *
     * @throws \ReflectionException
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
     *
     * @throws \SwooleTW\Http\Exceptions\SandboxException
     */
    public function enable()
    {
        if (!$this->config instanceof ConfigContract) {
            throw new SandboxException('Please initialize after setting base app.');
        }

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
     *
     * @param \Illuminate\Contracts\Container\Container|mixed $app
     */
    public function setInstance(Container $app)
    {
        $app->instance('app', $app);
        $app->instance(Container::class, $app);

        if ($this->framework === 'lumen') {
            $app->instance(LumenApplication::class, $app);
        }

        ContainerFacade::setInstance($app);
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
        Context::removeData('_request');
    }

    /**
     * Get current request.
     */
    public function getRequest()
    {
        return Context::getData('_request');
    }
}
