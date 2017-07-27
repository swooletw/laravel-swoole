<?php

namespace HuangYi\Http\Transformer;

use Illuminate\Http\Request as IlluminateRequest;
use Swoole\Http\Request as SwooleRequest;
use Symfony\Component\HttpFoundation\ParameterBag;

class RequestTransformer
{
    /**
     * Transform swoole request to illuminate request.
     *
     * @param \Swoole\Http\Request $swooleRequest
     * @return \Illuminate\Http\Request
     * @throws \LogicException
     */
    public static function toIlluminate(SwooleRequest $swooleRequest)
    {
        list($get, $post, $cookie, $files, $server, $content)
            = self::transformParameters($swooleRequest);

        return self::createIlluminateRequest($get, $post, $cookie, $files, $server, $content);
    }

    /**
     * Transforms request parameters.
     *
     * @param \Swoole\Http\Request $request
     * @return array
     */
    protected static function transformParameters(SwooleRequest $request)
    {
        $get = isset($request->get) ? $request->get : [];
        $post = isset($request->post) ? $request->post : [];
        $cookie = isset($request->cookie) ? $request->cookie : [];
        $files = isset($request->files) ? $request->files : [];
        $header = isset($request->header) ? $request->header : [];
        $server = isset($request->server) ? $request->server : [];
        $server = self::transformServerParameters($server, $header);
        $content = $request->rawContent();

        return [$get, $post, $cookie, $files, $server, $content];
    }

    /**
     * Transforms $_SERVER array.
     *
     * @param array $server
     * @param array $header
     * @return array
     */
    protected static function transformServerParameters(array $server, array $header)
    {
        $__SERVER = [];

        foreach ($server as $key => $value) {
            $key = strtoupper($key);
            $__SERVER[$key] = $value;
        }

        foreach ($header as $key => $value) {
            $key = str_replace('-', '_', $key);
            $key = strtoupper($key);

            if (! in_array($key, ['REMOTE_ADDR', 'SERVER_PORT', 'HTTPS'])) {
                $key = 'HTTP_' . $key;
            }

            $__SERVER[$key] = $value;
        }

        return $__SERVER;
    }

    /**
     * Create illuminate request.
     *
     * @param array $get
     * @param array $post
     * @param array $cookie
     * @param array $files
     * @param array $server
     * @param string $content
     * @return \Illuminate\Http\Request
     * @throws \LogicException
     */
    protected static function createIlluminateRequest($get, $post, $cookie, $files, $server, $content)
    {
        IlluminateRequest::enableHttpMethodParameterOverride();

        /*
        |--------------------------------------------------------------------------
        | Copy from \Symfony\Component\HttpFoundation\Request::createFromGlobals().
        |--------------------------------------------------------------------------
        |
        | With the php's bug #66606, the php's built-in web server
        | stores the Content-Type and Content-Length header values in
        | HTTP_CONTENT_TYPE and HTTP_CONTENT_LENGTH fields.
        |
        */

        if ('cli-server' === PHP_SAPI) {
            if (array_key_exists('HTTP_CONTENT_LENGTH', $server)) {
                $server['CONTENT_LENGTH'] = $server['HTTP_CONTENT_LENGTH'];
            }
            if (array_key_exists('HTTP_CONTENT_TYPE', $server)) {
                $server['CONTENT_TYPE'] = $server['HTTP_CONTENT_TYPE'];
            }
        }

        $request = new IlluminateRequest($get, $post, [], $cookie, $files, $server, $content);

        if (0 === strpos($request->headers->get('CONTENT_TYPE'), 'application/x-www-form-urlencoded')
            && in_array(strtoupper($request->server->get('REQUEST_METHOD', 'GET')), array('PUT', 'DELETE', 'PATCH'))
        ) {
            parse_str($request->getContent(), $data);
            $request->request = new ParameterBag($data);
        }

        return IlluminateRequest::createFromBase($request);
    }
}
