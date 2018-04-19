<?php

namespace SwooleTW\Http;

use SwooleTW\Http\Websocket\Websocket;
use Illuminate\Support\ServiceProvider;
use SwooleTW\Http\Commands\HttpServerCommand;
use SwooleTW\Http\Websocket\Rooms\RoomContract;

abstract class HttpServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigs();
        $this->registerManager();
        $this->registerCommands();
        $this->registerRoom();
        $this->registerWebsocket();
    }

    /**
     * Bind room instance to Laravel app container.
     */
    protected function registerRoom()
    {
        $this->app->singleton(RoomContract::class, function ($app) {
            $driver = $app['config']->get('swoole_websocket.default');
            $configs = $app['config']->get("swoole_websocket.settings.{$driver}");
            $className = $app['config']->get("swoole_websocket.drivers.{$driver}");

            $websocketRoom = new $className($configs);
            $websocketRoom->prepare();

            return $websocketRoom;
        });
        $this->app->alias(RoomContract::class, 'swoole.room');
    }

    /**
     * Bind websocket instance to Laravel app container.
     */
    protected function registerWebsocket()
    {
        $this->app->singleton(Websocket::class, function ($app) {
            return new Websocket($app['swoole.room']);
        });
        $this->app->alias(Websocket::class, 'swoole.websocket');
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
            __DIR__ . '/../config/swoole_websocket.php' => base_path('config/swoole_websocket.php')
        ], 'config');
        $this->bootRoutes();
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
     * Register commands.
     */
    protected function registerCommands()
    {
        $this->commands([
            HttpServerCommand::class,
        ]);
    }
}
