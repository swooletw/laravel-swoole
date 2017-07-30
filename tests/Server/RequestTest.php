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
use HuangYi\Http\Tests\Stubs\SwooleRequest;
use HuangYi\Http\Tests\TestCase;
use Illuminate\Http\Request as IlluminateRequest;

class RequestTest extends TestCase
{
    public function testMake()
    {
        $request = Request::make(new SwooleRequest);

        $this->assertInstanceOf(Request::class, $request);
    }

    public function testToIlluminate()
    {
        $illuminateRequest = Request::make(new SwooleRequest)->toIlluminate();

        $this->assertInstanceOf(IlluminateRequest::class, $illuminateRequest);
    }
}
