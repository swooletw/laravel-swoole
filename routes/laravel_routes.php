<?php


use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register socket.io routes for your application.
|
*/

Route::group(['namespace' => 'SwooleTW\Http\Controllers'], function () {
    Route::get('socket.io', 'SocketIOController@upgrade');
    Route::post('socket.io', 'SocketIOController@reject');
});
