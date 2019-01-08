<?php

namespace SwooleTW\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class SocketIOController
{
    protected $transports = ['polling', 'websocket'];

    public function upgrade(Request $request)
    {
        if (! in_array($request->input('transport'), $this->transports)) {
            return response()->json(
                [
                    'code' => 0,
                    'message' => 'Transport unknown',
                ],
                400
            );
        }

        if ($request->has('sid')) {
            return '1:6';
        }

        $payload = json_encode(
            [
                'sid' => base64_encode(uniqid()),
                'upgrades' => ['websocket'],
                'pingInterval' => Config::get('swoole_websocket.ping_interval'),
                'pingTimeout' => Config::get('swoole_websocket.ping_timeout'),
            ]
        );

        return '97:0' . $payload . '2:40';
    }

    public function reject()
    {
        return response()->json(
            [
                'code' => 3,
                'message' => 'Bad request',
            ],
            400
        );
    }
}
