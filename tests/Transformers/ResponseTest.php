<?php

namespace SwooleTW\Http\Tests\Transformers;

use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\File\File;
use Illuminate\Http\Response as IlluminateResponse;
use Illuminate\Support\Str;
use Swoole\Http\Response as SwooleResponse;
use Swoole\Http\Request as SwooleRequest;
use SwooleTW\Http\Tests\TestCase;
use SwooleTW\Http\Transformers\Response;
use Mockery;

class ResponseTest extends TestCase
{
    public function testMake()
    {
        $response = Response::make(new IlluminateResponse, new SwooleResponse, new SwooleRequest);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testGetIlluminateResponse()
    {
        $response = Response::make(new IlluminateResponse, new SwooleResponse, new SwooleRequest);
        $illuminateResponse = $response->getIlluminateResponse();

        $this->assertInstanceOf(IlluminateResponse::class, $illuminateResponse);
    }

    public function testGetSwooleResponse()
    {
        $response = Response::make(new IlluminateResponse, new SwooleResponse, new SwooleRequest);
        $swooleResponse = $response->getSwooleResponse();

        $this->assertInstanceOf(SwooleResponse::class, $swooleResponse);
    }

    public function testGetSwooleRequest()
    {
        $response = Response::make(new IlluminateResponse, new SwooleResponse, new SwooleRequest);
        $swooleRequest = $response->getSwooleRequest();

        $this->assertInstanceOf(SwooleRequest::class, $swooleRequest);
    }

    public function testSendHeaders()
    {
        $headers = ['test' => ['123', '456']];
        $status = 200;

        // rawcookie
        $cookie1 = Mockery::mock(Cookie::class);
        $cookie1->shouldReceive('isRaw')->once()->andReturn(true);
        $cookie1->shouldReceive('getName')->once()->andReturn('Cookie_1_getName');
        $cookie1->shouldReceive('getValue')->once()->andReturn('Cookie_1_getValue');
        $cookie1->shouldReceive('getExpiresTime')->once()->andReturn('Cookie_1_getExpiresTime');
        $cookie1->shouldReceive('getPath')->once()->andReturn('Cookie_1_getPath');
        $cookie1->shouldReceive('getDomain')->once()->andReturn('Cookie_1_getDomain');
        $cookie1->shouldReceive('isSecure')->once()->andReturn('Cookie_1_isSecure');
        $cookie1->shouldReceive('isHttpOnly')->once()->andReturn('Cookie_1_isHttpOnly');

        // cookie
        $cookie2 = Mockery::mock(Cookie::class);
        $cookie2->shouldReceive('isRaw')->once()->andReturn(false);
        $cookie2->shouldReceive('getName')->once()->andReturn('Cookie_2_getName');
        $cookie2->shouldReceive('getValue')->once()->andReturn('Cookie_2_getValue');
        $cookie2->shouldReceive('getExpiresTime')->once()->andReturn('Cookie_2_getExpiresTime');
        $cookie2->shouldReceive('getPath')->once()->andReturn('Cookie_2_getPath');
        $cookie2->shouldReceive('getDomain')->once()->andReturn('Cookie_2_getDomain');
        $cookie2->shouldReceive('isSecure')->once()->andReturn('Cookie_2_isSecure');
        $cookie2->shouldReceive('isHttpOnly')->once()->andReturn('Cookie_2_isHttpOnly');

        $illuminateResponse = Mockery::mock(IlluminateResponse::class);
        $illuminateResponse->headers = Mockery::mock(ResponseHeaderBag::class);

        $illuminateResponse->headers
            ->shouldReceive('has')
            ->once()
            ->with('Date')
            ->andReturn(false);

        $illuminateResponse->headers
            ->shouldReceive('getCookies')
            ->once()
            ->andReturn([$cookie1, $cookie2] );

        $illuminateResponse->headers
            ->shouldReceive('allPreserveCase')
            ->once()
            ->andReturn([
                    'Set-Cookie' => uniqid(),
                ] + $headers);

        $illuminateResponse->shouldReceive('setDate')
            ->once()
            ->withArgs(function(\DateTime $dateTime) {
                $timestamp = $dateTime->getTimestamp();
                return $timestamp <= time() && $timestamp + 2 > time();
            });

        $illuminateResponse->shouldReceive('getStatusCode')
            ->once()
            ->andReturn($status);

        $swooleResponse = Mockery::mock(SwooleResponse::class);
        $swooleResponse->shouldReceive('header')
            ->times(2)
            ->withArgs(function ($name, $value) use (&$headers) {
                $header = array_shift($headers['test']);
                return $name === 'test' && $header === $value;
            });
        $swooleResponse->shouldReceive('status')
            ->once()
            ->withArgs([$status]);
        $swooleResponse->shouldReceive('rawcookie')
            ->once()
            ->withArgs([
                'Cookie_1_getName',
                'Cookie_1_getValue',
                'Cookie_1_getExpiresTime',
                'Cookie_1_getPath',
                'Cookie_1_getDomain',
                'Cookie_1_isSecure',
                'Cookie_1_isHttpOnly'
            ]);
        $swooleResponse->shouldReceive('cookie')
            ->once()
            ->withArgs([
                'Cookie_2_getName',
                'Cookie_2_getValue',
                'Cookie_2_getExpiresTime',
                'Cookie_2_getPath',
                'Cookie_2_getDomain',
                'Cookie_2_isSecure',
                'Cookie_2_isHttpOnly'
            ]);

        $swooleRequest = Mockery::mock(SwooleRequest::class);

        $response = Response::make($illuminateResponse, $swooleResponse, $swooleRequest);

        /**
         * use Closure::call to bypass protect and private method
         * url: https://www.php.net/manual/en/closure.call.php
         */
        $callback = function() {
            $this->sendHeaders();
        };
        $callback->call($response);
    }

    public function testSendStreamedResponseContent()
    {
        $illuminateResponse = Mockery::mock(StreamedResponse::class);
        $illuminateResponse->output = uniqid();

        $swooleResponse = Mockery::mock(SwooleResponse::class);
        $swooleResponse->shouldReceive('end')
            ->once()
            ->withArgs([$illuminateResponse->output]);

        $swooleRequest = Mockery::mock(SwooleRequest::class);

        $response = Response::make($illuminateResponse, $swooleResponse, $swooleRequest);

        /**
         * use Closure::call to bypass protect and private method
         * url: https://www.php.net/manual/en/closure.call.php
         */
        $callback = function() {
            $this->sendContent();
        };
        $callback->call($response);
    }

    public function testSendBinaryFileResponseContent()
    {
        $path = uniqid();
        $file = Mockery::mock(File::class);
        $file->shouldReceive('getPathname')
            ->once()
            ->andReturn($path);

        $illuminateResponse = Mockery::mock(BinaryFileResponse::class);
        $illuminateResponse->shouldReceive('getFile')
            ->once()
            ->andReturn($file);

        $swooleResponse = Mockery::mock(SwooleResponse::class);
        $swooleResponse->shouldReceive('sendfile')
            ->once()
            ->withArgs([$path]);

        $swooleRequest = Mockery::mock(SwooleRequest::class);

        $response = Response::make($illuminateResponse, $swooleResponse, $swooleRequest);

        /**
         * use Closure::call to bypass protect and private method
         * url: https://www.php.net/manual/en/closure.call.php
         */
        $callback = function() {
            $this->sendContent();
        };
        $callback->call($response);
    }

    public function testSendChunkedContent()
    {
        $http_compression_level = 5;
        $content = Str::random(Response::CHUNK_SIZE * 3);
        $compressedContent = gzencode($content, $http_compression_level);
        $times = (int)ceil(strlen($compressedContent) / Response::CHUNK_SIZE);

        $chunks = [];
        foreach (str_split($compressedContent, Response::CHUNK_SIZE) as $chunk) {
            $chunks[] = $chunk;
        }

        app()->instance('config', new \Illuminate\Config\Repository([
            'swoole_http' => [
                'server' => [
                    'options' => [
                        'http_compression' => true,
                        'http_compression_level' => $http_compression_level
                    ]
                ],
            ],
        ]));

        $illuminateResponse = Mockery::mock(IlluminateResponse::class);
        $illuminateResponse->headers = Mockery::mock(ResponseHeaderBag::class);

        $illuminateResponse->headers
            ->shouldReceive('get')
            ->once()
            ->withArgs(['Content-Encoding'])
            ->andReturn(null);

        $illuminateResponse->shouldReceive('getContent')
            ->andReturn($content);

        $swooleResponse = Mockery::mock(SwooleResponse::class);
        $swooleResponse->shouldReceive('header')
            ->once()
            ->withArgs(['Content-Encoding', 'gzip']);

        $swooleResponse->shouldReceive('write')
            ->times($times)
            ->withArgs(function ($chunk) use (&$chunks) {
                $expectChunk = array_shift($chunks);
                return $chunk === $expectChunk;
            });
        $swooleResponse->shouldReceive('end')
            ->once();

        $swooleRequest = Mockery::mock(SwooleRequest::class);
        $swooleRequest->header = ['accept-encoding' => 'gzip, deflate, br'];

        $response = Response::make($illuminateResponse, $swooleResponse, $swooleRequest);

        /**
         * use Closure::call to bypass protect and private method
         * url: https://www.php.net/manual/en/closure.call.php
         */
        $callback = function() {
            $this->sendContent();
        };
        $callback->call($response);
    }

    public function testSend_()
    {
        $status = 200;
        $content = 'test';

        app()->instance('config', new \Illuminate\Config\Repository([
            'swoole_http' => [
                'server' => [
                    'options' => [
                        'http_compression' => false,
                    ]
                ],
            ],
        ]));

        $swooleResponse = Mockery::mock(SwooleResponse::class);
        $swooleResponse->shouldReceive('header')
            ->twice()
            ->withArgs(function ($name, $value) {
                return in_array($name, ['Date', 'Cache-Control'], true);
            });
        $swooleResponse->shouldReceive('status')
            ->once()
            ->with(200);
        $swooleResponse->shouldReceive('end')
            ->once()
            ->withArgs([$content]);

        $swooleRequest = Mockery::mock(SwooleRequest::class);
        $swooleRequest->header = ['accept-encoding' => 'gzip, deflate, br'];

        $response = Response::make($content, $swooleResponse, $swooleRequest);
        $response->send();
    }
}
