<?php

namespace SwooleTW\Http;

use SwooleTW\Http\Server\Manager;
use SwooleTW\Http\Middleware\AccessLog;

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
            return new Manager($app, 'lumen');
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
        $app = $this->app;

        // router only exists after lumen 5.5
        if (property_exists($app, 'router')) {
            $app->router->group(['namespace' => 'SwooleTW\Http\Controllers'], function ($app) {
                require __DIR__ . '/../routes/lumen_routes.php';
            });
        } else {
            $app->group(['namespace' => 'App\Http\Controllers'], function ($app) {
                require __DIR__ . '/../routes/lumen_routes.php';
            });
        }
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
