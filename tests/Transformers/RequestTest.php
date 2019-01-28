<?php

namespace SwooleTW\Http\Tests\Transformers;

use Illuminate\Http\Request as IlluminateRequest;
use Mockery as m;
use Swoole\Http\Request as SwooleRequest;
use SwooleTW\Http\Tests\TestCase;
use SwooleTW\Http\Transformers\Request;

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

    public function testHandleStatic()
    {
        $isFile = false;
        $this->mockMethod('is_file', function () use (&$isFile) {
            return $isFile = true;
        });

        $fileSize = false;
        $this->mockMethod('filesize', function () use (&$fileSize) {
            $fileSize = true;

            return 1;
        });

        $this->mockMethod('pathinfo', function () {
            return 'css?id=bfaf14972de9d89ae8fc';
        });

        $response = m::mock('response');
        $response->shouldReceive('status')
                 ->with(200)
                 ->once();
        $response->shouldReceive('header')
                 ->with('Content-Type', 'text/css')
                 ->once();
        $response->shouldReceive('sendfile')
                 ->with('/foo.bar')
                 ->once();

        Request::handleStatic(new SwooleRequestStub, $response, '/');

        $this->assertTrue($isFile);
        $this->assertTrue($fileSize);
    }

    public function testHandleStaticWithBlackList()
    {
        $request = new SwooleRequestStub;
        $request->server['request_uri'] = 'foo.php';

        $result = Request::handleStatic($request, null, '/');
        $this->assertFalse($result);
    }

    public function testHandleStaticWithNoneFile()
    {
        $isFile = false;
        $this->mockMethod('is_file', function () use (&$isFile) {
            $isFile = true;

            return false;
        });

        $result = Request::handleStatic(new SwooleRequestStub, null, '/');
        $this->assertFalse($result);
        $this->assertTrue($isFile);
    }

    protected function mockMethod($name, \Closure $function, $namespace = null)
    {
        parent::mockMethod($name, $function, 'SwooleTW\Http\Transformers');
    }
}

class SwooleRequestStub extends SwooleRequest
{
    public $get = [];

    public $post = [];

    public $header = ['foo' => 'bar'];

    public $server = [
        'HTTP_CONTENT_LENGTH' => 0,
        'CONTENT_LENGTH' => 0,
        'HTTP_CONTENT_TYPE' => null,
        'CONTENT_TYPE' => null,
        'request_uri' => 'foo.bar',
    ];

    public $cookie = [];

    public $files = [];

    public $fd = 1;

    function rawContent()
    {
        return 'foo=bar';
    }
}
