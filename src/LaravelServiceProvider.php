<?php

namespace SwooleTW\Http;

use Illuminate\Contracts\Http\Kernel;
use SwooleTW\Http\Middleware\AccessLog;
use SwooleTW\Http\Server\Manager;
use SwooleTW\Http\Server\PidManager;

/**
 * @codeCoverageIgnore
 */
class LaravelServiceProvider extends HttpServiceProvider
{
    /**
     * Register manager.
     *
     * @return void
     */
    protected function registerManager()
    {
        $this->app->singleton(Manager::class, function ($app) {
            return new Manager($app, 'laravel', base_path(), $this->app[PidManager::class]);
        });

        $this->app->alias(Manager::class, 'swoole.manager');
    }

    /**
     * Boot websocket routes.
     *
     * @return void
     */
    protected function bootWebsocketRoutes()
    {
        require __DIR__ . '/../routes/laravel_routes.php';
    }

    /**
     * Register access log middleware to container.
     *
     * @return void
     */
    protected function pushAccessLogMiddleware()
    {
        $this->app->make(Kernel::class)->pushMiddleware(AccessLog::class);
    }
}
