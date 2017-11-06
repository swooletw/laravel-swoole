<?php

$app = new Laravel\Lumen\Application(
    realpath(__DIR__ . '/../')
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    Illuminate\Foundation\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    Illuminate\Foundation\Console\Kernel::class
);

$app->register(SwooleTW\Http\LumenServiceProvider::class);

$app->group(['namespace' => 'App\Http\Controllers'], function ($app) {
    require __DIR__ . '/../routes/web.php';
});

return $app;
