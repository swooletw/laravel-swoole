<?php

use Illuminate\Http\Request;
use SwooleTW\Http\Websocket\Facades\Websocket;

/*
|--------------------------------------------------------------------------
| Websocket Routes
|--------------------------------------------------------------------------
|
| Here is where you can register websocket events for your application.
|
*/

/** @scrutinizer ignore-call */
Websocket::on('connect', function ($websocket, Request $request) {
    // called while socket on connect
});

/** @scrutinizer ignore-call */
Websocket::on('disconnect', function ($websocket) {
    // called while socket on disconnect
});

/** @scrutinizer ignore-call */
Websocket::on('example', function ($websocket, $data) {
    $websocket->emit('message', $data);
});
