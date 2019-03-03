<?php

namespace SwooleTW\Http\Tests\Transformers;

use Illuminate\Http\Response as IlluminateResponse;
use Swoole\Http\Response as SwooleResponse;
use SwooleTW\Http\Tests\TestCase;
use SwooleTW\Http\Transformers\Response;

class ResponseTest extends TestCase
{
    public function testMake()
    {
        $response = Response::make(new IlluminateResponse, new SwooleResponse);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testGetIlluminateResponse()
    {
        $response = Response::make(new IlluminateResponse, new SwooleResponse);
        $illuminateResponse = $response->getIlluminateResponse();

        $this->assertInstanceOf(IlluminateResponse::class, $illuminateResponse);
    }

    public function testGetSwooleResponse()
    {
        $response = Response::make(new IlluminateResponse, new SwooleResponse);
        $swooleResponse = $response->getSwooleResponse();

        $this->assertInstanceOf(SwooleResponse::class, $swooleResponse);
    }
}
