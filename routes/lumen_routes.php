<?php

/*
|--------------------------------------------------------------------------
| Socket.io Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

/** @scrutinizer ignore-call */
$app->get('socket.io', [
    'as' => 'io.get', 'uses' => 'SwooleTW\Http\Controllers\SocketIOController@upgrade'
]);

/** @scrutinizer ignore-call */
$app->post('socket.io', [
    'as' => 'io.post', 'uses' => 'SwooleTW\Http\Controllers\SocketIOController@reject'
]);
