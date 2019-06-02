<?php

namespace SwooleTW\Http;

use SwooleTW\Http\Middleware\AccessLog;
use SwooleTW\Http\Server\Manager;
use SwooleTW\Http\Server\PidManager;

/**
 * @codeCoverageIgnore
 */
class LumenServiceProvider extends HttpServiceProvider
{
    /**
     * Register manager.
     *
     * @return void
     */
    protected function registerManager()
    {
        $this->app->singleton(Manager::class, function ($app) {
            return new Manager($app, 'lumen', base_path(), $this->app[PidManager::class]);
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
        $this->app->router
            ->group(['namespace' => 'SwooleTW\Http\Controllers'], function ($router) {
                require __DIR__ . '/../routes/lumen_routes.php';
            });
    }

    /**
     * Register access log middleware to container.
     *
     * @return void
     */
    protected function pushAccessLogMiddleware()
    {
        $this->app->middleware(AccessLog::class);
    }
}
