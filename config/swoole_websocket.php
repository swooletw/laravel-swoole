<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Websocket handler for onOpen and onClose callback
    | Replace this handler before you start it
    |--------------------------------------------------------------------------
    */
    'handler' => SwooleTW\Http\Websocket\SocketIO\WebsocketHandler::class,

    /*
    |--------------------------------------------------------------------------
    | Websocket route file path
    |--------------------------------------------------------------------------
    */
    'route_file' => base_path('routes/websocket.php'),

    /*
    |--------------------------------------------------------------------------
    | Default websocket driver
    |--------------------------------------------------------------------------
    */
    'default' => 'table',

    /*
    |--------------------------------------------------------------------------
    | Default frame parser
    | Replace it if you want to customize your websocket payload
    |--------------------------------------------------------------------------
    */
    'parser' => SwooleTW\Http\Websocket\SocketIO\SocketIOParser::class,

    /*
    |--------------------------------------------------------------------------
    | Heartbeat interval (ms)
    |--------------------------------------------------------------------------
    */
    'ping_interval' => 25000,

    /*
    |--------------------------------------------------------------------------
    | Heartbeat interval timeout (ms)
    |--------------------------------------------------------------------------
    */
    'ping_timeout' => 60000,

    /*
    |--------------------------------------------------------------------------
    | Drivers mapping
    |--------------------------------------------------------------------------
    */
    'drivers' => [
        'table' => SwooleTW\Http\Websocket\Rooms\TableRoom::class,
        'redis' => SwooleTW\Http\Websocket\Rooms\RedisRoom::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Drivers settings
    |--------------------------------------------------------------------------
    */
    'settings' => [

        'table' => [
            'room_rows' => 4096,
            'room_size' => 2048,
            'client_rows' => 8192,
            'client_size' => 2048
        ],

        'redis' => [
            'server' => [
                'host' => env('REDIS_HOST', '127.0.0.1'),
                'password' => env('REDIS_PASSWORD', null),
                'port' => env('REDIS_PORT', 6379),
                'database' => 0,
                'persistent' => true,
            ],
            'options' => [
                //
            ],
            'prefix' => 'swoole:',
        ]
    ],
];
