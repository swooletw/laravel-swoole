<?php

namespace SwooleTW\Http\Server;

use Exception;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Str;
use SwooleTW\Http\Concerns\InteractsWithSwooleTable;
use SwooleTW\Http\Concerns\InteractsWithWebsocket;
use SwooleTW\Http\Concerns\WithApplication;
use SwooleTW\Http\Helpers\OS;
use SwooleTW\Http\Task\SwooleTaskJob;
use SwooleTW\Http\Transformers\Request;
use SwooleTW\Http\Transformers\Response;
use SwooleTW\Http\Websocket\Websocket;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Throwable;

/**
 * Class Manager
 */
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
     * Server events.
     *
     * @var array
     */
    protected $events = [
        'start',
        'shutDown',
        'workerStart',
        'workerStop',
        'packet',
        'bufferFull',
        'bufferEmpty',
        'task',
        'finish',
        'pipeMessage',
        'workerError',
        'managerStart',
        'managerStop',
        'request',
    ];

    /**
     * HTTP server manager constructor.
     *
     * @param \Illuminate\Contracts\Container\Container $container
     * @param string $framework
     * @param string $basePath
     *
     * @throws \Exception
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
        $this->container->make('swoole.server')->start();
    }

    /**
     * Stop swoole server.
     */
    public function stop()
    {
        $this->container->make('swoole.server')->shutdown();
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
            $listener = Str::camel("on_$event");
            $callback = method_exists($this, $listener) ? [$this, $listener] : function () use ($event) {
                $this->container->make('events')->fire("swoole.$event", func_get_args());
            };

            $this->container->make('swoole.server')->on($event, $callback);
        }
    }

    /**
     * "onStart" listener.
     */
    public function onStart()
    {
        $this->setProcessName('master process');
        $this->createPidFile();

        $this->container->make('events')->fire('swoole.start', func_get_args());
    }

    /**
     * The listener of "managerStart" event.
     *
     * @return void
     */
    public function onManagerStart()
    {
        $this->setProcessName('manager process');
        $this->container->make('events')->fire('swoole.managerStart', func_get_args());
    }

    /**
     * "onWorkerStart" listener.
     *
     * @param \Swoole\Http\Server|mixed $server
     *
     * @throws \Exception
     */
    public function onWorkerStart($server)
    {
        $this->clearCache();

        $this->container->make('events')->fire('swoole.workerStart', func_get_args());

        // don't init laravel app in task workers
        if ($server->taskworker) {
            $this->setProcessName('task process');

            return;
        }
        $this->setProcessName('worker process');

        // clear events instance in case of repeated listeners in worker process
        Facade::clearResolvedInstance('events');

        // prepare laravel app
        $this->getApplication();

        // bind after setting laravel app
        $this->bindToLaravelApp();

        // prepare websocket handler and routes
        if ($this->isServerWebsocket) {
            $this->prepareWebsocketHandler();
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
        $this->app->make('events')->fire('swoole.request');

        $this->resetOnRequest();
        $handleStatic = $this->container->make('config')->get('swoole_http.handle_static_files', true);
        $publicPath = $this->container->make('config')->get('swoole_http.server.public_path', base_path('public'));

        try {
            // handle static file request first
            if ($handleStatic && Request::handleStatic($swooleRequest, $swooleResponse, $publicPath)) {
                return;
            }
            // transform swoole request to illuminate request
            $illuminateRequest = Request::make($swooleRequest)->toIlluminate();

            // set current request to sandbox
            $this->app->make('swoole.sandbox')->setRequest($illuminateRequest);
            // enable sandbox
            $this->app->make('swoole.sandbox')->enable();

            // handle request via laravel/lumen's dispatcher
            $illuminateResponse = $this->app->make('swoole.sandbox')->run($illuminateRequest);
            $response = Response::make($illuminateResponse, $swooleResponse);
            $response->send();
        } catch (Throwable $e) {
            try {
                $exceptionResponse = $this->app->make(ExceptionHandler::class)->render(null, $e);
                $response = Response::make($exceptionResponse, $swooleResponse);
                $response->send();
            } catch (Throwable $e) {
                $this->logServerError($e);
            }
        } finally {
            // disable and recycle sandbox resource
            $this->app->make('swoole.sandbox')->disable();
        }
    }

    /**
     * Reset on every request.
     */
    protected function resetOnRequest()
    {
        // Reset websocket data
        if ($this->isServerWebsocket) {
            $this->app->make('swoole.websocket')->reset(true);
        }
    }

    /**
     * Set onTask listener.
     *
     * @param mixed $server
     * @param string $taskId
     * @param string $srcWorkerId
     * @param mixed $data
     */
    public function onTask($server, $taskId, $srcWorkerId, $data)
    {
        $this->container->make('events')->fire('swoole.task', func_get_args());

        try {
            // push websocket message
            if (is_array($data)) {
                if ($this->isServerWebsocket
                    && array_key_exists('action', $data)
                    && $data['action'] === Websocket::PUSH_ACTION) {
                    $this->pushMessage($server, $data['data'] ?? []);
                }
                // push async task to queue
            } else {
                if (is_string($data)) {
                    $decoded = \json_decode($data, true);

                    if (JSON_ERROR_NONE === \json_last_error() && isset($decoded['job'])) {
                        (new SwooleTaskJob($this->container, $server, $data, $taskId, $srcWorkerId))->fire();
                    }
                }
            }
        } catch (Throwable $e) {
            $this->logServerError($e);
        }
    }

    /**
     * Set onFinish listener.
     *
     * @param mixed $server
     * @param string $taskId
     * @param mixed $data
     */
    public function onFinish($server, $taskId, $data)
    {
        // task worker callback
        $this->container->make('events')->fire('swoole.finish', func_get_args());

        return;
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

        if ($this->isServerWebsocket) {
            $this->bindRoom();
            $this->bindWebsocket();
        }
    }

    /**
     * Bind sandbox to Laravel app container.
     */
    protected function bindSandbox()
    {
        $this->app->singleton(Sandbox::class, function ($app) {
            return new Sandbox($app, $this->framework);
        });

        $this->app->alias(Sandbox::class, 'swoole.sandbox');
    }

    /**
     * Gets pid file path.
     *
     * @return string
     */
    protected function getPidFile()
    {
        return $this->container->make('config')->get('swoole_http.server.options.pid_file');
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
        if (extension_loaded('apc')) {
            apc_clear_cache();
        }

        if (extension_loaded('Zend OPcache')) {
            opcache_reset();
        }
    }

    /**
     * Set process name.
     *
     * @codeCoverageIgnore
     *
     * @param $process
     */
    protected function setProcessName($process)
    {
        // MacOS doesn't support modifying process name.
        if (OS::is(OS::MAC_OS) || $this->isInTesting()) {
            return;
        }
        $serverName = 'swoole_http_server';
        $appName = $this->container->make('config')->get('app.name', 'Laravel');

        $name = sprintf('%s: %s for %s', $serverName, $process, $appName);

        swoole_set_process_name($name);
    }

    /**
     * Indicates if it's in phpunit environment.
     *
     * @return bool
     */
    protected function isInTesting()
    {
        return defined('IN_PHPUNIT') && IN_PHPUNIT;
    }

    /**
     * Log server error.
     *
     * @param \Throwable|\Exception $e
     */
    public function logServerError(Throwable $e)
    {
        if (! $e instanceof Exception) {
            $e = new FatalThrowableError($e);
        }

        $this->container->make(ExceptionHandler::class)->report($e);
    }
}
