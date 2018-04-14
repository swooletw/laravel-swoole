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
    | Default message formatter
    | Replace it if you want to customize your websocket payload
    |--------------------------------------------------------------------------
    */
    'formatter' => SwooleTW\Http\Websocket\Formatters\DefaultFormatter::class,

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
