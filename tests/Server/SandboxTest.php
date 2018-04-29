<?php

namespace SwooleTW\Http\Tests\Server;

use Mockery as m;
use ReflectionProperty;
use RuntimeException;
use SwooleTW\Http\Server\Sandbox;
use SwooleTW\Http\Tests\TestCase;
use Illuminate\Container\Container;
use SwooleTW\Http\Server\Application;

class SandboxTest extends TestCase
{
    public function testGetSandbox()
    {
        $this->assertTrue($this->getSandbox() instanceof Sandbox);
    }

    public function testMakeSandbox()
    {
        $application = m::mock(Application::class);
        $sandbox = Sandbox::make($application);

        $this->assertTrue($sandbox instanceof Sandbox);
    }

    public function testGetApplication()
    {
        $this->assertTrue($this->getSandbox()->getApplication() instanceof Application);
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

        $reflector = new \ReflectionClass(Sandbox::class);

        $property = $reflector->getProperty('snapshot');
        $property->setAccessible(true);
        $property->setValue($sandbox, $application);

        return $sandbox;
    }

    protected function getContainer()
    {
        $container = m::mock(Container::class);
        $container->shouldReceive('bootstrapWith')
            ->andReturnNull();

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
