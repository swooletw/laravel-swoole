<?php

namespace SwooleTW\Http\Controllers;

use Illuminate\Http\Request;

class SocketIOController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function upgrade(Request $request)
    {
        if ($request->has('sid')) {
            return '1:6';
        }

        $payload = json_encode([
            'sid' => base64_encode(uniqid()),
            'upgrades' => ['websocket'],
            'pingInterval' => 25000,
            'pingTimeout' => 60000
        ]);

        return '97:0' . $payload . '2:40';
    }

    public function reject(Request $request)
    {
        return response()->json([
            'code' => 3,
            'message' => 'Bad handshake method'
        ], 400);
    }
}
