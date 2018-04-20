<?php

/*
|--------------------------------------------------------------------------
| Websocket Routes
|--------------------------------------------------------------------------
|
| Here is where you can register websocket events for your application.
|
*/

Websocket::on('example', function ($websocket) {
    $websocket->emit('message', 'hello world!');
});

// Websocket::on('test', 'ExampleController@method');