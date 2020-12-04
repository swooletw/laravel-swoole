<?php

namespace SwooleTW\Http\Transformers;

use Illuminate\Http\Response as IlluminateResponse;
use Swoole\Http\Response as SwooleResponse;
use Swoole\Http\Request as SwooleRequest;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Response
{
    const CHUNK_SIZE = 8192;

    /**
     * @var \Swoole\Http\Response
     */
    protected $swooleResponse;

    /**
     * @var \Swoole\Http\Request
     */
    protected $swooleRequest;

    /**
     * @var \Illuminate\Http\Response
     */
    protected $illuminateResponse;

    /**
     * Make a response.
     *
     * @param $illuminateResponse
     * @param \Swoole\Http\Response $swooleResponse
     * @param \Swoole\Http\Request $swooleRequest
     *
     * @return \SwooleTW\Http\Transformers\Response
     */
    public static function make($illuminateResponse, SwooleResponse $swooleResponse, SwooleRequest $swooleRequest)
    {
        return new static($illuminateResponse, $swooleResponse, $swooleRequest);
    }

    /**
     * Response constructor.
     *
     * @param mixed $illuminateResponse
     * @param \Swoole\Http\Response $swooleResponse
     * @param \Swoole\Http\Request $swooleRequest
     */
    public function __construct($illuminateResponse, SwooleResponse $swooleResponse, SwooleRequest $swooleRequest)
    {
        $this->setIlluminateResponse($illuminateResponse);
        $this->setSwooleResponse($swooleResponse);
        $this->setSwooleRequest($swooleRequest);
    }

    /**
     * Send HTTP headers and content.
     *
     * @throws \InvalidArgumentException
     */
    public function send()
    {
        $this->sendHeaders();
        $this->sendContent();
    }

    /**
     * Send HTTP headers.
     *
     * @throws \InvalidArgumentException
     */
    protected function sendHeaders()
    {
        $illuminateResponse = $this->getIlluminateResponse();

        /* RFC2616 - 14.18 says all Responses need to have a Date */
        if (! $illuminateResponse->headers->has('Date')) {
            $illuminateResponse->setDate(\DateTime::createFromFormat('U', time()));
        }

        // headers
        // allPreserveCaseWithoutCookies() doesn't exist before Laravel 5.3
        $headers = $illuminateResponse->headers->allPreserveCase();
        if (isset($headers['Set-Cookie'])) {
            unset($headers['Set-Cookie']);
        }
        foreach ($headers as $name => $values) {
            foreach ($values as $value) {
                $this->swooleResponse->header($name, $value);
            }
        }

        // status
        $this->swooleResponse->status($illuminateResponse->getStatusCode());

        // cookies
        // $cookie->isRaw() is supported after symfony/http-foundation 3.1
        // and Laravel 5.3, so we can add it back now
        foreach ($illuminateResponse->headers->getCookies() as $cookie) {
            $method = $cookie->isRaw() ? 'rawcookie' : 'cookie';
            $this->swooleResponse->$method(
                $cookie->getName(),
                $cookie->getValue(),
                $cookie->getExpiresTime(),
                $cookie->getPath(),
                $cookie->getDomain(),
                $cookie->isSecure(),
                $cookie->isHttpOnly()
            );
        }
    }

    /**
     * Send HTTP content.
     */
    protected function sendContent()
    {
        $illuminateResponse = $this->getIlluminateResponse();

        if ($illuminateResponse instanceof StreamedResponse && property_exists($illuminateResponse, 'output')) {
            // TODO Add Streamed Response with output
            $this->swooleResponse->end($illuminateResponse->output);
        } elseif ($illuminateResponse instanceof BinaryFileResponse) {
            $this->swooleResponse->sendfile($illuminateResponse->getFile()->getPathname());
        } else {
            $chunkGzip = $this->canGzipContent($illuminateResponse->headers->get('Content-Encoding'));
            $this->sendInChunk($illuminateResponse->getContent(), $chunkGzip);
        }
    }

    /**
     * Send content in chunk
     *
     * @param string $content
     * @param bool $chunkGzip
     */
    protected function sendInChunk($content, $chunkGzip)
    {
        if (strlen($content) <= static::CHUNK_SIZE) {
            $this->swooleResponse->end($content);
            return;
        }

        // Swoole Chunk mode does not support compress by default, this patch only supports gzip
        if ($chunkGzip) {
            $this->swooleResponse->header('Content-Encoding', 'gzip');
            $content = gzencode($content, config('swoole_http.server.options.http_compression_level', 3));
        }

        foreach (str_split($content, static::CHUNK_SIZE) as $chunk) {
            $this->swooleResponse->write($chunk);
        }

        $this->swooleResponse->end();
    }

    /**
     * @param \Swoole\Http\Response $swooleResponse
     *
     * @return \SwooleTW\Http\Transformers\Response
     */
    protected function setSwooleResponse(SwooleResponse $swooleResponse)
    {
        $this->swooleResponse = $swooleResponse;

        return $this;
    }

    /**
     * @return \Swoole\Http\Response
     */
    public function getSwooleResponse()
    {
        return $this->swooleResponse;
    }

    /**
     * @param mixed illuminateResponse
     *
     * @return \SwooleTW\Http\Transformers\Response
     */
    protected function setIlluminateResponse($illuminateResponse)
    {
        if (! $illuminateResponse instanceof SymfonyResponse) {
            $content = (string) $illuminateResponse;
            $illuminateResponse = new IlluminateResponse($content);
        }

        $this->illuminateResponse = $illuminateResponse;

        return $this;
    }

    /**
     * @return \Illuminate\Http\Response
     */
    public function getIlluminateResponse()
    {
        return $this->illuminateResponse;
    }

    /**
     * @param \Swoole\Http\Request $swooleRequest
     *
     * @return \SwooleTW\Http\Transformers\Response
     */
    protected function setSwooleRequest(SwooleRequest $swooleRequest)
    {
        $this->swooleRequest = $swooleRequest;

        return $this;
    }

    /**
     * @return \Swoole\Http\Request
     */
    public function getSwooleRequest()
    {
        return $this->swooleRequest;
    }

    /**
     * @param string $responseContentEncoding
     * @return bool
     */
    protected function canGzipContent($responseContentEncoding)
    {
        return empty($responseContentEncoding) &&
            config('swoole_http.server.options.http_compression', true) &&
            !empty($this->swooleRequest->header['accept-encoding']) &&
            strpos($this->swooleRequest->header['accept-encoding'], 'gzip') !== false &&
            function_exists('gzencode');
    }
}
