<?php

namespace SwooleTW\Http\Tests\Server;

use SwooleTW\Http\Server\Application;
use SwooleTW\Http\Tests\TestCase;
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

    public function testLaravelResetProvider()
    {
        $application = Application::make('laravel', $this->basePath . '/laravel');
        $response = $application->run(Request::create('/'));

        $app = $application->getApplication();

        $this->assertSame('bar', $app['singleton.test']->foo);

        $app->singleton('singleton.test', function () {
            $obj = new \stdClass;
            $obj->foo = 'foo';

            return $obj;
        });
        $this->assertSame('foo', $app['singleton.test']->foo);

        $response = $application->resetProviders();
        $this->assertSame('bar', $app['singleton.test']->foo);
    }

    public function testLumenResetProvider()
    {
        $application = Application::make('lumen', $this->basePath . '/lumen');
        $response = $application->run(Request::create('/'));

        $app = $application->getApplication();

        $this->assertSame('bar', $app['singleton.test']->foo);

        $app->singleton('singleton.test', function () {
            $obj = new \stdClass;
            $obj->foo = 'foo';

            return $obj;
        });
        $this->assertSame('foo', $app['singleton.test']->foo);

        $response = $application->resetProviders();
        $this->assertSame('bar', $app['singleton.test']->foo);
    }
}
