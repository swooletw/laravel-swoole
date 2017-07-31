<?php

/*
 * This file is part of the huang-yi/laravel-swoole-http package.
 *
 * (c) Huang Yi <coodeer@163.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HuangYi\Http\Tests\Server;

use HuangYi\Http\Server\Request;
use HuangYi\Http\Tests\TestCase;
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
