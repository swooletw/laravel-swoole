<?php

namespace SwooleTW\Http\Tests\Fixtures\Laravel\App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{

    public function map()
    {
        $this->mapWebRoutes();
    }

    protected function mapWebRoutes()
    {
        require __DIR__ . '/../../routes/web.php';
    }
}
