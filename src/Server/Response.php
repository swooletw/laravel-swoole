<?php

/*
 * This file is part of the huang-yi/laravel-swoole-http package.
 *
 * (c) Huang Yi <coodeer@163.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HuangYi\Http\Server;

use Illuminate\Http\Response as IlluminateResponse;
use Swoole\Http\Response as SwooleResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Response
{
    /**
     * @var \Swoole\Http\Response
     */
    protected $swooleResponse;

    /**
     * @var \Illuminate\Http\Response
     */
    protected $illuminateResponse;

    /**
     * Make a response.
     *
     * @param $illuminateResponse
     * @param \Swoole\Http\Response $swooleResponse
     * @return \HuangYi\Http\Server\Response
     */
    public static function make($illuminateResponse, SwooleResponse $swooleResponse)
    {
        return new static($illuminateResponse, $swooleResponse);
    }

    /**
     * Response constructor.
     *
     * @param mixed $illuminateResponse
     * @param \Swoole\Http\Response $swooleResponse
     */
    public function __construct($illuminateResponse, SwooleResponse $swooleResponse)
    {
        $this->setIlluminateResponse($illuminateResponse);
        $this->setSwooleResponse($swooleResponse);
    }

    /**
     * Sends HTTP headers and content.
     *
     * @throws \InvalidArgumentException
     */
    public function send()
    {
        $this->sendHeaders();
        $this->sendContent();
    }

    /**
     * Sends HTTP headers.
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
        foreach ($illuminateResponse->headers->allPreserveCaseWithoutCookies() as $name => $values) {
            foreach ($values as $value) {
                $this->swooleResponse->header($name, $value);
            }
        }

        // status
        $this->swooleResponse->status($illuminateResponse->getStatusCode());

        // cookies
        foreach ($illuminateResponse->headers->getCookies() as $cookie) {
            $this->swooleResponse->cookie(
                $cookie->getName(), $cookie->getValue(),
                $cookie->getExpiresTime(), $cookie->getPath(),
                $cookie->getDomain(), $cookie->isSecure(),
                $cookie->isHttpOnly()
            );
        }
    }

    /**
     * Sends HTTP content.
     */
    protected function sendContent()
    {
        $illuminateResponse = $this->getIlluminateResponse();

        if ($illuminateResponse instanceof StreamedResponse) {
            $illuminateResponse->sendContent();
        } elseif ($illuminateResponse instanceof BinaryFileResponse) {
            $this->swooleResponse->sendfile($illuminateResponse->getFile()->getPathname());
        } else {
            $this->swooleResponse->end($illuminateResponse->getContent());
        }
    }

    /**
     * @param \Swoole\Http\Response $swooleResponse
     * @return \HuangYi\Http\Server\Response
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
     * @return \HuangYi\Http\Server\Response
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
}
