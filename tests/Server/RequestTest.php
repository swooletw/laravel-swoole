<?php

namespace SwooleTW\Http\Tests\Server;

use SwooleTW\Http\Server\Request;
use SwooleTW\Http\Tests\TestCase;
use Illuminate\Http\Request as IlluminateRequest;
use Swoole\Http\Request as SwooleRequest;

class RequestTest extends TestCase
{
    public function testMake()
    {
        $request = Request::make(new SwooleRequestStub);

        $this->assertInstanceOf(Request::class, $request);
    }

    public function testToIlluminate()
    {
        $illuminateRequest = Request::make(new SwooleRequestStub)->toIlluminate();

        $this->assertInstanceOf(IlluminateRequest::class, $illuminateRequest);
    }
}

class SwooleRequestStub extends SwooleRequest
{
    public $get = [];
    public $post = [];
    public $header = [];
    public $server = [];
    public $cookie = [];
    public $files = [];
    public $fd = 1;

    function rawContent()
    {
        return 'foo=bar';
    }
}
