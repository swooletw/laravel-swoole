<?php

namespace SwooleTW\Http\Transformers;

use Illuminate\Http\Request as IlluminateRequest;
use Illuminate\Http\Response as IlluminateResponse;
use Swoole\Http\Request as SwooleRequest;
use SwooleTW\Http\Helpers\FW;
use SwooleTW\Http\Helpers\MimeType;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

/**
 * Class Request
 */
class Request
{
    /**
     * Blacklisted extensions
     *
     * @const array
     */
    protected const EXTENSION_BLACKLIST = ['php', 'htaccess', 'config'];

    /**
     * Extension mime types
     */
    protected const EXTENSION_MIMES = ['js' => 'text/javascript', 'css' => 'text/css'];

    /**
     * @var \Illuminate\Http\Request
     */
    protected $illuminateRequest;

    /**
     * Make a request.
     *
     * @param \Swoole\Http\Request $swooleRequest
     *
     * @return \SwooleTW\Http\Transformers\Request
     */
    public static function make(SwooleRequest $swooleRequest)
    {
        return new static(...static::toIlluminateParameters($swooleRequest));
    }

    /**
     * Request constructor.
     *
     * @param array $params provides GET, POST, COOKIE, FILES, SERVER, CONTENT
     */
    public function __construct(...$params)
    {
        $this->createIlluminateRequest(...$params);
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
     *
     * @return void
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
            && in_array(strtoupper($request->server->get('REQUEST_METHOD', 'GET')), ['PUT', 'DELETE', 'PATCH'])
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
     *
     * @return array
     */
    protected static function toIlluminateParameters(SwooleRequest $request)
    {
        $get = $request->get ?? [];
        $post = $request->post ?? [];
        $cookie = $request->cookie ?? [];
        $files = $request->files ?? [];
        $header = $request->header ?? [];
        $server = $request->server ?? [];
        $server = static::transformServerParameters($server, $header);
        $content = $request->rawContent();

        return [$get, $post, $cookie, $files, $server, $content];
    }

    /**
     * Transforms $_SERVER array.
     *
     * @param array $server
     * @param array $header
     *
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
     * @param string $publicPath
     *
     * @return boolean
     */
    public static function handleStatic($swooleRequest, $swooleResponse, string $publicPath)
    {
        $uri = $swooleRequest->server['request_uri'] ?? '';
        $extension = strtok(pathinfo($uri, PATHINFO_EXTENSION), '?');
        $fileName = $publicPath . $uri;

        if ($extension && in_array($extension, static::EXTENSION_BLACKLIST)) {
            return false;
        }

        if (! is_file($fileName) || ! filesize($fileName)) {
            return false;
        }

        $swooleResponse->status(IlluminateResponse::HTTP_OK);
        $swooleResponse->header('Content-Type', MimeType::get($extension));
        $swooleResponse->sendfile($fileName);

        return true;
    }
}
