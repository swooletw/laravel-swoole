<?php

namespace SwooleTW\Http\AsyncTask;

use SwooleTW\Http\AsyncTask\Connectors\AsyncTaskConnector;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Add the connector to the queue drivers.
     *
     * @return void
     */
    public function boot()
    {
        $swooleServer = $this->app['swoole.http'];

        $this->app['queue']->addConnector('swoole_async_task', function () {
            return new AsyncTaskConnector($this->app['swoole.http']->getServer());
        });
    }
}
