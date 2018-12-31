<?php

namespace SwooleTW\Http\Tests\Fixtures\Laravel\App\Providers;

use Illuminate\Support\ServiceProvider;

class TestServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('singleton.test', function () {
            $obj = new \stdClass;
            $obj->foo = 'bar';

            return $obj;
        });
    }
}
