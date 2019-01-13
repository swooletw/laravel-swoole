<?php

namespace SwooleTW\Http;

use SwooleTW\Http\Server\Manager;

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
            return new Manager($app, 'laravel');
        });

        $this->app->alias(Manager::class, 'swoole.manager');
    }

    /**
     * Boot routes.
     *
     * @return void
     */
    protected function bootRoutes()
    {
        require __DIR__ . '/../routes/laravel_routes.php';
    }
}
