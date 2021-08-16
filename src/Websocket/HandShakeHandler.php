<?php

namespace SwooleTW\Http\Websocket;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Class HandShakeHandler
 */
class HandShakeHandler
{
    /**
     * @see https://www.swoole.co.uk/docs/modules/swoole-websocket-server
     *
     * @param  Request  $request
     *
     * @return Response
     */
    public function handle($request)
    {
        $response = new Response();
        $socketkey = $request->headers->get('sec-websocket-key');

        if (0 === preg_match('#^[+/0-9A-Za-z]{21}[AQgw]==$#', $socketkey) || 16 !== strlen(base64_decode($socketkey))) {
            return $response->setStatusCode(403);
        }

        $headers = [
            'Upgrade' => 'websocket',
            'Connection' => 'Upgrade',
            'Sec-WebSocket-Accept' => base64_encode(sha1($socketkey.'258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true)),
            'Sec-WebSocket-Version' => '13',
        ];
        if (isset($request->header['sec-websocket-protocol'])) {
            $headers['Sec-WebSocket-Protocol'] = $request->header['sec-websocket-protocol'];
        }

        foreach ($headers as $key => $header) {
            $request->headers->set($key, $header);
        }

        $response->setStatusCode(101);
        return $response;
    }
}
