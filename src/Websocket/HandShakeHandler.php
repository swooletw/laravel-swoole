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
        /** @var Response $response */
        $response = \response();
        $socketkey = $request->header['sec-websocket-key'];

        if (0 === preg_match('#^[+/0-9A-Za-z]{21}[AQgw]==$#', $socketkey) || 16 !== strlen(base64_decode($socketkey))) {
            return $response->setContent('')->setStatusCode(403, 'Not Allowed');
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

        foreach ($headers as $header => $val) {
            $response->header($header, $val);
        }

        $response->setStatusCode(101);
        return $response;
    }
}
