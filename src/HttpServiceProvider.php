<?php

namespace SwooleTW\Http;

use Illuminate\Queue\QueueManager;
use SwooleTW\Http\Server\PidManager;
use Swoole\Http\Server as HttpServer;
use Illuminate\Support\ServiceProvider;
use SwooleTW\Http\Server\Facades\Server;
use SwooleTW\Http\Commands\HttpServerCommand;
use Swoole\Websocket\Server as WebsocketServer;
use SwooleTW\Http\Task\Connectors\SwooleTaskConnector;

/**
 * @codeCoverageIgnore
 */
abstract class HttpServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * @var boolean
     */
    protected $isWebsocket = false;

    /**
     * @var \Swoole\Http\Server | \Swoole\Websocket\Server
     */
    protected static $server;

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishFiles();
        $this->loadConfigs();
        $this->mergeConfigs();
        $this->setIsWebsocket();

        $config = $this->app->make('config');

        if ($config->get('swoole_http.websocket.enabled')) {
            $this->bootWebsocketRoutes();
        }

        if ($config->get('swoole_http.server.access_log')) {
            $this->pushAccessLogMiddleware();
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerServer();
        $this->registerManager();
        $this->registerCommands();
        $this->registerPidManager();
        $this->registerSwooleQueueDriver();
    }

    /**
     * Register manager.
     *
     * @return void
     */
    abstract protected function registerManager();

    /**
     * Boot websocket routes.
     *
     * @return void
     */
    abstract protected function bootWebsocketRoutes();

    /**
     * Register access log middleware to container.
     *
     * @return void
     */
    abstract protected function pushAccessLogMiddleware();

    /**
     * Publish files of this package.
     */
    protected function publishFiles()
    {
        $this->publishes([
            __DIR__ . '/../config/swoole_http.php' => base_path('config/swoole_http.php'),
            __DIR__ . '/../config/swoole_websocket.php' => base_path('config/swoole_websocket.php'),
            __DIR__ . '/../routes/websocket.php' => base_path('routes/websocket.php'),
        ], 'laravel-swoole');
    }

    /**
     * Load configurations.
     */
    protected function loadConfigs()
    {
        // do nothing
    }

    /**
     * Merge configurations.
     */
    protected function mergeConfigs()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/swoole_http.php', 'swoole_http');
        $this->mergeConfigFrom(__DIR__ . '/../config/swoole_websocket.php', 'swoole_websocket');
    }

    /**
     * Register pid manager.
     *
     * @return void
     */
    protected function registerPidManager(): void
    {
        $this->app->singleton(PidManager::class, function() {
            return new PidManager(
                $this->app->make('config')->get('swoole_http.server.options.pid_file')
            );
        });
    }

    /**
     * Set isWebsocket.
     */
    protected function setIsWebsocket()
    {
        $this->isWebsocket = $this->app->make('config')
            ->get('swoole_http.websocket.enabled');
    }

    /**
     * Register commands.
     */
    protected function registerCommands()
    {
        $this->commands([
            HttpServerCommand::class,
        ]);
    }

    /**
     * Create swoole server.
     */
    protected function createSwooleServer()
    {
        $server = $this->isWebsocket ? WebsocketServer::class : HttpServer::class;
        $config = $this->app->make('config');
        $host = $config->get('swoole_http.server.host');
        $port = $config->get('swoole_http.server.port');
        $socketType = $config->get('swoole_http.server.socket_type', SWOOLE_SOCK_TCP);
        $processType = $config->get('swoole_http.server.process_type', SWOOLE_PROCESS);

        static::$server = new $server($host, $port, $processType, $socketType);
    }

    /**
     * Set swoole server configurations.
     */
    protected function configureSwooleServer()
    {
        $config = $this->app->make('config');
        $options = $config->get('swoole_http.server.options');

        // only enable task worker in websocket mode and for queue driver
        if ($config->get('queue.default') !== 'swoole' && ! $this->isWebsocket) {
            unset($options['task_worker_num']);
        }

        static::$server->set($options);
    }

    /**
     * Register manager.
     *
     * @return void
     */
    protected function registerServer()
    {
        $this->app->singleton(Server::class, function () {
            if (is_null(static::$server)) {
                $this->createSwooleServer();
                $this->configureSwooleServer();
            }

            return static::$server;
        });
        $this->app->alias(Server::class, 'swoole.server');
    }

    /**
     * Register queue driver for swoole async task.
     */
    protected function registerSwooleQueueDriver()
    {
        $this->app->afterResolving('queue', function (QueueManager $manager) {
            $manager->addConnector('swoole', function () {
                return new SwooleTaskConnector($this->app->make(Server::class));
            });
        });
    }
}
