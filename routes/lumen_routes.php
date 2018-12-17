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

$app->get('socket.io', [
    'as' => 'io.get', 'uses' => 'SocketIOController@upgrade',
]);

$app->post('socket.io', [
    'as' => 'io.post', 'uses' => 'SocketIOController@reject',
]);
