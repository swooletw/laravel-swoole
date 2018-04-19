<?php

namespace SwooleTW\Http;

use SwooleTW\Http\Server\Manager;

class LumenServiceProvider extends HttpServiceProvider
{
    /**
     * Register manager.
     *
     * @return void
     */
    protected function registerManager()
    {
        $this->app->singleton('swoole.http', function ($app) {
            return new Manager($app, 'lumen');
        });
    }

    /**
     * Boot routes.
     *
     * @return void
     */
    protected function bootRoutes()
    {
        $app = $this->app;

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
}
