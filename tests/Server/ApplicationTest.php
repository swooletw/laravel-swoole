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

use HuangYi\Http\Server\Application;
use HuangYi\Http\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ApplicationTest extends TestCase
{
    protected $basePath = __DIR__ . '/../fixtures';

    public function testMake()
    {
        $application = Application::make('laravel', $this->basePath . '/laravel');

        $this->assertInstanceOf(Application::class, $application);
    }

    public function testRunLaravel()
    {
        $application = Application::make('laravel', $this->basePath . '/laravel');
        $response = $application->run(Request::create('/'));

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('welcome', $response->getContent());
    }

    public function testRunLumen()
    {
        $application = Application::make('lumen', $this->basePath . '/lumen');
        $response = $application->run(Request::create('/'));

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('hello', $response->getContent());
    }

    public function testRunOther()
    {
        $this->expectException(\Exception::class);

        $application = Application::make('other', $this->basePath . '/laravel');
        $application->run(Request::create('/'));
    }

    public function testTerminate()
    {
        $flag = false;

        $application = Application::make('laravel', $this->basePath . '/laravel');
        $request = Request::create('/');
        $response = $application->run($request);

        $application->getApplication()->terminating(function () use (&$flag) {
            $flag = true;
        });

        $application->terminate($request, $response);

        $this->assertTrue($flag);
    }
}
