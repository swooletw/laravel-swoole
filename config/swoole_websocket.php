<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Websocket handler for onOpen and onClose callback
    | Replace this handler before you start it
    |--------------------------------------------------------------------------
    */
    'handler' => SwooleTW\Http\Websocket\WebsocketHandler::class,

    /*
    |--------------------------------------------------------------------------
    | Websocket handlers mapping for onMessage callback
    |--------------------------------------------------------------------------
    */
    'handlers' => [
        // 'event_name' => 'App\Handlers\ExampleHandler@function',
    ],

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
    | Drivers mapping
    |--------------------------------------------------------------------------
    */
    'drivers' => [
        'table' => SwooleTW\Http\Websocket\Rooms\TableRoom::class,
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
        ]
    ],
];
