<?php

namespace SwooleTW\Http\Tests\Transformers;

use Illuminate\Http\Response as IlluminateResponse;
use Swoole\Http\Response as SwooleResponse;
use Swoole\Http\Request as SwooleRequest;
use SwooleTW\Http\Tests\TestCase;
use SwooleTW\Http\Transformers\Response;

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
}
