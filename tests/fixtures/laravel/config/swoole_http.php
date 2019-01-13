<?php

return [

    /*
    |--------------------------------------------------------------------------
    | HTTP server configurations.
    |--------------------------------------------------------------------------
    |
    | @see https://wiki.swoole.com/wiki/page/274.html
    |
    */

    'server' => [
        'host' => '127.0.0.1',
        'port' => '1215',
        'options' => [
            'pid_file' => base_path('storage/logs/swoole_http.pid'),
            'daemonize' => 0,
        ],
    ],
    'providers' => [
        SwooleTW\Http\Tests\Fixtures\Laravel\App\Providers\TestServiceProvider::class,
    ],
];
