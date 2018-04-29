<?php

use SwooleTW\Http\Websocket\Facades\Websocket;

/*
|--------------------------------------------------------------------------
| Websocket Routes
|--------------------------------------------------------------------------
|
| Here is where you can register websocket events for your application.
|
*/

Websocket::on('example', function ($websocket, $data) {
    $websocket->emit('message', $data);
});

// Websocket::on('test', 'ExampleController@method');