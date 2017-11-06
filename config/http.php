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
        'host' => env('HTTP_SERVER_HOST', '127.0.0.1'),
        'port' => env('HTTP_SERVER_PORT', '1215'),
        'options' => [
            'pid_file' => env('HTTP_SERVER_OPTIONS_PID_FILE', base_path('storage/logs/http.pid')),
            'log_file' => env('HTTP_SERVER_OPTIONS_LOG_FILE', base_path('storage/logs/http.log')),
            'daemonize' => env('HTTP_SERVER_OPTIONS_DAEMONIZE', 1),
        ],
    ],
];
