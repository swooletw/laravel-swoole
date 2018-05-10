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
        $application = $this->makeApplication();

        $this->assertInstanceOf(Application::class, $application);
    }

    public function testMakeInvalidFramework()
    {
        $this->expectException(\Exception::class);

        $this->makeApplication('other');
    }

    public function testRun()
    {
        $application = $this->makeApplication();
        $response = $application->run(Request::create('/'));

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('welcome', $response->getContent());
    }

    public function testTerminate()
    {
        $flag = false;

        if (class_exists('\Laravel\Lumen\Application')) {
            $this->assertTrue(true);

            return;
        }

        $application = $this->makeApplication();
        $request = Request::create('/');
        $response = $application->run($request);

        $application->getApplication()->terminating(function () use (&$flag) {
            $flag = true;
        });

        $application->terminate($request, $response);

        $this->assertTrue($flag);
    }

    protected function makeApplication($forceFramework = null)
    {
        if (! is_null($forceFramework)) {
            $framework = $forceFramework;
        } elseif (class_exists('\Illuminate\Foundation\Application')) {
            $framework = 'laravel';
        } elseif (class_exists('\Laravel\Lumen\Application')) {
            $framework = 'lumen';
        } else {
            $framework = 'other';
        }

        return Application::make($framework, $this->basePath . '/' . $framework);
    }
}
