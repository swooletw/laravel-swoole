<?php

namespace SwooleTW\Http\Transformers;

use Illuminate\Http\Request as IlluminateRequest;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class Request
{
    const BLACK_LIST = ['php', 'htaccess', 'config'];

    /**
     * @var \Illuminate\Http\Request
     */
    protected $illuminateRequest;

    /**
     * Make a request.
     *
     * @param \Swoole\Http\Request $swooleRequest
     * @return \SwooleTW\Http\Server\Request
     */
    public static function make(SwooleRequest $swooleRequest)
    {
        list($get, $post, $cookie, $files, $server, $content)
            = static::toIlluminateParameters($swooleRequest);

        return new static($get, $post, $cookie, $files, $server, $content);
    }

    /**
     * Request constructor.
     *
     * @param array $get
     * @param array $post
     * @param array $cookie
     * @param array $files
     * @param array $server
     * @param string $content
     * @throws \LogicException
     */
    public function __construct(array $get, array $post, array $cookie, array $files, array $server, $content = null)
    {
        $this->createIlluminateRequest($get, $post, $cookie, $files, $server, $content);
    }

    /**
     * Create Illuminate Request.
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
    protected function createIlluminateRequest($get, $post, $cookie, $files, $server, $content = null)
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

        $request = new SymfonyRequest($get, $post, [], $cookie, $files, $server, $content);

        if (0 === strpos($request->headers->get('CONTENT_TYPE'), 'application/x-www-form-urlencoded')
            && in_array(strtoupper($request->server->get('REQUEST_METHOD', 'GET')), array('PUT', 'DELETE', 'PATCH'))
        ) {
            parse_str($request->getContent(), $data);
            $request->request = new ParameterBag($data);
        }

        $this->illuminateRequest = IlluminateRequest::createFromBase($request);
    }

    /**
     * @return \Illuminate\Http\Request
     */
    public function toIlluminate()
    {
        return $this->getIlluminateRequest();
    }

    /**
     * @return \Illuminate\Http\Request
     */
    public function getIlluminateRequest()
    {
        return $this->illuminateRequest;
    }

    /**
     * Transforms request parameters.
     *
     * @param \Swoole\Http\Request $request
     * @return array
     */
    protected static function toIlluminateParameters(SwooleRequest $request)
    {
        $get = isset($request->get) ? $request->get : [];
        $post = isset($request->post) ? $request->post : [];
        $cookie = isset($request->cookie) ? $request->cookie : [];
        $files = isset($request->files) ? $request->files : [];
        $header = isset($request->header) ? $request->header : [];
        $server = isset($request->server) ? $request->server : [];
        $server = static::transformServerParameters($server, $header);
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
     * Handle static request.
     *
     * @param \Swoole\Http\Request $swooleRequest
     * @param \Swoole\Http\Response $swooleResponse
     * @param string $path
     * @return boolean
     */
    public static function handleStatic($swooleRequest, $swooleResponse, string $publicPath)
    {
        $uri = $swooleRequest->server['request_uri'] ?? '';
        $extension = substr(strrchr($uri, '.'), 1);
        if ($extension && in_array($extension, static::BLACK_LIST)) {
            return;
        }

        $filename = $publicPath . $uri;
        if (! is_file($filename) || filesize($filename) === 0) {
            return;
        }

        $swooleResponse->status(200);
        $mime = mime_content_type($filename);
        if ($extension === 'js') {
            $mime = 'text/javascript';
        } elseif ($extension === 'css') {
            $mime = 'text/css';
        }
        $swooleResponse->header('Content-Type', $mime);
        $swooleResponse->sendfile($filename);

        return true;
    }
}
