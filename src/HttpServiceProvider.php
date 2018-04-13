<?php

namespace SwooleTW\Http;

use SwooleTW\Http\Server\Websocket;
use Illuminate\Support\ServiceProvider;
use SwooleTW\Http\Server\Room\RoomContract;
use SwooleTW\Http\Commands\HttpServerCommand;

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
        $this->registerWebsocket();
    }

    /**
     * Register manager.
     *
     * @return void
     */
    abstract protected function registerManager();

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

    /**
     * Register websocket.
     */
    protected function registerWebsocket()
    {
        if (! $this->app['config']->get('swoole_http.websocket.enabled')) {
            return;
        }

        // bind room instance
        $this->app->singleton(RoomContract::class, function ($app) {
            $driver = $app['config']->get('swoole_websocket.default');
            $configs = $app['config']->get("swoole_websocket.settings.{$driver}");
            $className = $app['config']->get("swoole_websocket.drivers.{$driver}");

            $room = new $className($configs);
            $room->prepare();

            return $room;
        });
        $this->app->alias(RoomContract::class, 'swoole.room');

        // bind websocket instance
        $this->app->singleton(Websocket::class, function ($app) {
            return new Websocket($app['swoole.room']);
        });
        $this->app->alias(Websocket::class, 'swoole.websocket');
    }
}
