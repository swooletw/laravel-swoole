<?php

namespace SwooleTW\Http\Server;

use Exception;
use Swoole\Http\Server;
use SwooleTW\Http\Server\Sandbox;
use Illuminate\Support\Facades\Facade;
use SwooleTW\Http\Websocket\Websocket;
use SwooleTW\Http\Transformers\Request;
use SwooleTW\Http\Transformers\Response;
use SwooleTW\Http\Concerns\WithApplication;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;
use SwooleTW\Http\Concerns\InteractsWithWebsocket;
use SwooleTW\Http\Concerns\InteractsWithSwooleTable;

class Manager
{
    use InteractsWithWebsocket,
        InteractsWithSwooleTable,
        WithApplication;

    /**
     * Container.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * @var string
     */
    protected $framework;

    /**
     * @var string
     */
    protected $basePath;

    /**
     * @var \SwooleTW\Http\Server\Sandbox
     */
    protected $sandbox;

    /**
     * Server events.
     *
     * @var array
     */
    protected $events = [
        'start', 'shutDown', 'workerStart', 'workerStop', 'packet',
        'bufferFull', 'bufferEmpty', 'task', 'finish', 'pipeMessage',
        'workerError', 'managerStart', 'managerStop', 'request',
    ];

    /**
     * HTTP server manager constructor.
     *
     * @param \Swoole\Http\Server $server
     * @param \Illuminate\Contracts\Container\Container $container
     * @param string $framework
     * @param string $basePath
     */
    public function __construct(Container $container, $framework, $basePath = null)
    {
        $this->container = $container;
        $this->setFramework($framework);
        $this->setBasepath($basePath);
        $this->initialize();
    }

    /**
     * Run swoole server.
     */
    public function run()
    {
        $this->container['swoole.server']->start();
    }

    /**
     * Stop swoole server.
     */
    public function stop()
    {
        $this->container['swoole.server']->shutdown();
    }

    /**
     * Initialize.
     */
    protected function initialize()
    {
        $this->createTables();
        $this->prepareWebsocket();
        $this->setSwooleServerListeners();
    }

    /**
     * Set swoole server listeners.
     */
    protected function setSwooleServerListeners()
    {
        foreach ($this->events as $event) {
            $listener = 'on' . ucfirst($event);

            if (method_exists($this, $listener)) {
                $this->container['swoole.server']->on($event, [$this, $listener]);
            } else {
                $this->container['swoole.server']->on($event, function () use ($event) {
                    $event = sprintf('swoole.%s', $event);

                    $this->container['events']->fire($event, func_get_args());
                });
            }
        }
    }

    /**
     * "onStart" listener.
     */
    public function onStart()
    {
        $this->setProcessName('master process');
        $this->createPidFile();

        $this->container['events']->fire('swoole.start', func_get_args());
    }

    /**
      * The listener of "managerStart" event.
      *
      * @return void
      */
    public function onManagerStart()
    {
        $this->setProcessName('manager process');
        $this->container['events']->fire('swoole.managerStart', func_get_args());
    }

    /**
     * "onWorkerStart" listener.
     */
    public function onWorkerStart(Server $server)
    {
        $this->clearCache();
        $this->setProcessName('worker process');

        $this->container['events']->fire('swoole.workerStart', func_get_args());

        // don't init laravel app in task workers
        if ($server->taskworker) {
            return;
        }

        // clear events instance in case of repeated listeners in worker process
        Facade::clearResolvedInstance('events');

        // set application to sandbox environment
        $this->sandbox = Sandbox::make($this->getApplication())->setFramework($this->framework);

        // bind after setting laravel app
        $this->bindToLaravelApp();

        // load websocket handlers after binding websocket to laravel app
        if ($this->isWebsocket) {
            $this->setWebsocketHandler();
            $this->loadWebsocketRoutes();
        }
    }

    /**
     * "onRequest" listener.
     *
     * @param \Swoole\Http\Request $swooleRequest
     * @param \Swoole\Http\Response $swooleResponse
     */
    public function onRequest($swooleRequest, $swooleResponse)
    {
        $this->app['events']->fire('swoole.request');

        $this->resetOnRequest();
        $handleStatic = $this->container['config']->get('swoole_http.handle_static_files', true);
        $publicPath = $this->container['config']->get('swoole_http.server.public_path', base_path('public'));

        try {
            // handle static file request first
            if ($handleStatic && Request::handleStatic($swooleRequest, $swooleResponse, $publicPath)) {
                return;
            }
            // transform swoole request to illuminate request
            $illuminateRequest = Request::make($swooleRequest)->toIlluminate();

            // set current request to sandbox
            $this->sandbox->setRequest($illuminateRequest);
            // enable sandbox
            $this->sandbox->enable();

            // handle request via laravel/lumen's dispatcher
            $illuminateResponse = $this->sandbox->run($illuminateRequest);
            $response = Response::make($illuminateResponse, $swooleResponse);
            $response->send();
        } catch (Exception $e) {
            try {
                $exceptionResponse = $this->app[ExceptionHandler::class]->render($illuminateRequest, $e);
                $response = Response::make($exceptionResponse, $swooleResponse);
                $response->send();
            } catch (Exception $e) {
                $this->logServerError($e);
            }
        } finally {
            // disable and recycle sandbox resource
            $this->sandbox->disable();
        }
    }

    /**
     * Reset on every request.
     */
    protected function resetOnRequest()
    {
        // Reset websocket data
        if ($this->isWebsocket) {
            $this->websocket->reset(true);
        }
    }

    /**
     * Set onTask listener.
     */
    public function onTask(Server $server, $taskId, $srcWorkerId, $data)
    {
        $this->container['events']->fire('swoole.task', func_get_args());

        try {
            // push websocket message
            if ($this->isWebsocket
                && array_key_exists('action', $data)
                && $data['action'] === Websocket::PUSH_ACTION) {
                $this->pushMessage($server, $data['data'] ?? []);
            }
        } catch (Exception $e) {
            $this->logServerError($e);
        }
    }

    /**
     * Set onFinish listener.
     */
    public function onFinish(Server $server, $taskId, $data)
    {
        // task worker callback
    }

    /**
     * Set onShutdown listener.
     */
    public function onShutdown()
    {
        $this->removePidFile();
    }

    /**
     * Set bindings to Laravel app.
     */
    protected function bindToLaravelApp()
    {
        $this->bindSandbox();
        $this->bindSwooleTable();

        if ($this->isWebsocket) {
            $this->bindRoom();
            $this->bindWebsocket();
        }
    }

    /**
     * Set isSandbox config.
     */
    protected function setIsSandbox()
    {
        $this->isSandbox = $this->container['config']->get('swoole_http.sandbox_mode', false);
    }

    /**
     * Bind sandbox to Laravel app container.
     */
    protected function bindSandbox()
    {
        $this->app->singleton('swoole.sandbox', function () {
            return $this->sandbox;
        });
    }

    /**
     * Gets pid file path.
     *
     * @return string
     */
    protected function getPidFile()
    {
        return $this->container['config']->get('swoole_http.server.options.pid_file');
    }

    /**
     * Create pid file.
     */
    protected function createPidFile()
    {
        $pidFile = $this->getPidFile();
        $pid = $this->container['swoole.server']->master_pid;

        file_put_contents($pidFile, $pid);
    }

    /**
     * Remove pid file.
     */
    protected function removePidFile()
    {
        $pidFile = $this->getPidFile();

        if (file_exists($pidFile)) {
            unlink($pidFile);
        }
    }

    /**
     * Clear APC or OPCache.
     */
    protected function clearCache()
    {
        if (function_exists('apc_clear_cache')) {
            apc_clear_cache();
        }

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }

    /**
     * Set process name.
     *
     * @param $process
     */
    protected function setProcessName($process)
    {
        // MacOS doesn't support modifying process name.
        if ($this->isMacOS()) {
            return;
        }
        $serverName = 'swoole_http_server';
        $appName = $this->container['config']->get('app.name', 'Laravel');

        $name = sprintf('%s: %s for %s', $serverName, $process, $appName);

        swoole_set_process_name($name);
    }

    /**
    * Determine whether the process is running in macOS.
    *
    * @return bool
    */
    protected function isMacOS()
    {
        return PHP_OS === 'Darwin';
    }

    /**
     * Log server error.
     *
     * @param Exception
     */
    protected function logServerError(Exception $e)
    {
        $this->app[ExceptionHandler::class]->report($e);
    }
}
