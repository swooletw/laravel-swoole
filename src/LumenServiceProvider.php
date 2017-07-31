<?php

namespace HuangYi\Http;

use HuangYi\Http\Server\Manager;

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
}
