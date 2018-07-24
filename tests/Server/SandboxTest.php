<?php

namespace SwooleTW\Http\Tests\Server;

use Mockery as m;
use RuntimeException;
use Swoole\Coroutine;
use ReflectionProperty;
use Illuminate\Http\Request;
use SwooleTW\Http\Server\Sandbox;
use SwooleTW\Http\Tests\TestCase;
use Illuminate\Container\Container;
use SwooleTW\Http\Server\Application;
use Illuminate\Support\Facades\Facade;

class SandboxTest extends TestCase
{
    public function testGetSandbox()
    {
        $this->assertTrue($this->getSandbox() instanceof Sandbox);
    }

    public function testMakeSandbox()
    {
        $sandbox = Sandbox::make($this->getApplication());

        $this->assertTrue($sandbox instanceof Sandbox);
    }

    public function testGetApplication()
    {
        $this->assertTrue($this->getSandbox()->getApplication() instanceof Application);
    }

    public function testSetRequest()
    {
        $request = m::mock(Request::class);
        $sandbox = $this->getSandbox()->setRequest($request);

        $this->assertSame($request, $sandbox->getRequest());
    }

    public function testSetSnapshot()
    {
        $application = $this->getApplication();
        $application->foo = 'bar';
        $sandbox = $this->getSandbox()->setSnapshot($application);

        $this->assertSame($application, $sandbox->getSnapshot());
    }

    protected function getSandbox()
    {
        $container = $this->getContainer();
        $application = $this->getApplication();

        $reflector = new \ReflectionClass(Application::class);

        $property = $reflector->getProperty('application');
        $property->setAccessible(true);
        $property->setValue($application, $container);

        $sandbox = new Sandbox($application);

        return $sandbox;
    }

    protected function getContainer()
    {
        $config = m::mock(Illuminate\Config\Repository::class);
        $config->shouldReceive('get')
            ->andReturn([]);
        $container = m::mock(Container::class);
        $container->shouldReceive('offsetGet')
            ->andReturn((object)[]);
        $container->shouldReceive('make')
            ->andReturn($config);

        return $container;
    }

    protected function getApplication()
    {
        $application = m::mock(Application::class);
        $application->shouldReceive('getApplication')
            ->andReturn($this->getContainer());
        $application->shouldReceive('getFramework')
            ->andReturn('test');

        $reflector = new \ReflectionClass(Application::class);

        $property = $reflector->getProperty('application');
        $property->setAccessible(true);
        $property->setValue($application, $this->getContainer());

        return $application;
    }
}
