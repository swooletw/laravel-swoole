<?php

namespace SwooleTW\Http;


use Illuminate\Support\ServiceProvider;
use Swoole\Http\Server as HttpServer;
use Swoole\Websocket\Server as WebsocketServer;
use SwooleTW\Http\Commands\HttpServerCommand;
use SwooleTW\Http\Coroutine\Connectors\ConnectorFactory;
use SwooleTW\Http\Coroutine\Connectors\MySqlConnector;
use SwooleTW\Http\Coroutine\MySqlConnection;
use SwooleTW\Http\Helpers\FW;
use SwooleTW\Http\Server\Facades\Server;
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
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigs();
        $this->setIsWebsocket();
        $this->registerServer();
        $this->registerManager();
        $this->registerCommands();
        $this->registerDatabaseDriver();
        $this->registerSwooleQueueDriver();
    }

    /**
     * Register manager.
     *
     * @return void
     */
    abstract protected function registerManager();

    /**
     * Boot routes.
     *
     * @return void
     */
    abstract protected function bootRoutes();

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/swoole_http.php' => base_path('config/swoole_http.php'),
            __DIR__ . '/../config/swoole_websocket.php' => base_path('config/swoole_websocket.php'),
            __DIR__ . '/../routes/websocket.php' => base_path('routes/websocket.php'),
        ], 'laravel-swoole');

        if ($this->app->make('config')->get('swoole_http.websocket.enabled')) {
            $this->bootRoutes();
        }
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
     * Set isWebsocket.
     */
    protected function setIsWebsocket()
    {
        $this->isWebsocket = $this->app->make('config')->get('swoole_http.websocket.enabled');
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
        $host = $this->app->make('config')->get('swoole_http.server.host');
        $port = $this->app->make('config')->get('swoole_http.server.port');
        $socketType = $this->app->make('config')->get('swoole_http.server.socket_type', SWOOLE_SOCK_TCP);

        static::$server = new $server($host, $port, SWOOLE_PROCESS, $socketType);
    }

    /**
     * Set swoole server configurations.
     */
    protected function configureSwooleServer()
    {
        $config = $this->app->make('config');
        $options = $config->get('swoole_http.server.options');

        // only enable task worker in websocket mode and for queue driver
        if ($config->get('queue.default') !== 'swoole' && !$this->isWebsocket) {
            unset($config['task_worker_num']);
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
     * Register database driver for coroutine mysql.
     */
    protected function registerDatabaseDriver()
    {
        $this->app->extend('db', function ($db) {
            $db->extend('mysql-coroutine', function ($config, $name) {
                $config['name'] = $name;
                $connection = function () use ($config) {
                    return ConnectorFactory::make(FW::version())->connect($config);
                };
                $config = $this->getMergedDatabaseConfig($config, $name);

                $connection = new MySqlConnection(
                    $this->getNewMySqlConnection($config),
                    $config['database'],
                    $config['prefix'],
                    $config
                );

                if (isset($config['read'])) {
                    $connection->setReadPdo($this->getNewMySqlConnection($config));
                }

                return $connection;
            });

            return $db;
        });
    }

    /**
     * Get mereged config for coroutine mysql.
     */
    protected function getMergedDatabaseConfig(array $config, string $name)
    {
        $config['name'] = $name;

        if (isset($config['read'])) {
            $config = array_merge($config, $config['read']);
        }
        if (isset($config['write'])) {
            $config = array_merge($config, $config['write']);
        }

        return $config;
    }

    /**
     * Get a new mysql connection.
     */
    protected function getNewMySqlConnection(array $config)
    {
        return (new MySqlConnector())->connect($config);
    }

    /**
     * Register queue driver for swoole async task.
     */
    protected function registerSwooleQueueDriver()
    {
        $this->app->afterResolving('queue', function ($manager) {
            $manager->addConnector('swoole', function () {
                return new SwooleTaskConnector($this->app->make(Server::class));
            });
        });
    }
}
