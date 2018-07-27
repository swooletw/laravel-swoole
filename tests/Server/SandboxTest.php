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

    public function testGetApplication()
    {
        $this->assertTrue($this->getSandbox()->getApplication() instanceof Container);
    }

    public function testSetRequest()
    {
        $request = m::mock(Request::class);
        $sandbox = $this->getSandbox()->setRequest($request);

        $this->assertSame($request, $sandbox->getRequest());
    }

    public function testSetSnapshot()
    {
        $container = $this->getContainer();
        $sandbox = $this->getSandbox()->setSnapshot($container);

        $this->assertSame($container, $sandbox->getSnapshot());
    }

    protected function getSandbox()
    {
        $container = $this->getContainer();
        $reflector = new \ReflectionClass($container);

        $sandbox = new Sandbox($container);

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
}
