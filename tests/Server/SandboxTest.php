<?php

namespace SwooleTW\Http\Tests\Server;

use Mockery as m;
use RuntimeException;
use Swoole\Coroutine;
use ReflectionProperty;
use Illuminate\Http\Request;
use Illuminate\Config\Repository;
use SwooleTW\Http\Server\Sandbox;
use SwooleTW\Http\Tests\TestCase;
use Illuminate\Container\Container;
use SwooleTW\Http\Coroutine\Context;
use SwooleTW\Http\Server\Application;
use Illuminate\Support\Facades\Facade;
use SwooleTW\Http\Exceptions\SandboxException;

class SandboxTest extends TestCase
{
    public function testSetFramework()
    {
        $sandbox = new Sandbox;
        $sandbox->setFramework($framework = 'foo');

        $this->assertSame($framework, $sandbox->getFramework());
    }

    public function testSetBaseApp()
    {
        $container = m::mock(Container::class);

        $sandbox = new Sandbox;
        $sandbox->setBaseApp($container);

        $this->assertSame($container, $sandbox->getBaseApp());
    }

    public function testAppNotSetException()
    {
        $this->expectException(SandboxException::class);

        $sandbox = new Sandbox;
        $sandbox->initialize();
    }

    public function testInitialize()
    {
        $provider = m::mock('provider');
        $providerName = get_class($provider);

        $config = m::mock(Repository::class);
        $config->shouldReceive('get')
            ->with('swoole_http.providers', [])
            ->once()
            ->andReturn([$providerName]);

        $container = m::mock(Container::class);
        $container->shouldReceive('make')
            ->with('config')
            ->once()
            ->andReturn($config);

        $sandbox = new Sandbox;
        $sandbox->setBaseApp($container);
        $sandbox->initialize();

        $sandboxProvider = $sandbox->getProviders()[$providerName];

        $this->assertTrue($sandbox->getConfig() instanceof Repository);
        $this->assertSame($providerName, get_class($sandboxProvider));
    }

    public function testGetApplication()
    {
        $container = m::mock(Container::class);

        $sandbox = new Sandbox;
        $sandbox->setBaseApp($container);
        $sandbox->getApplication();

        $this->assertTrue($sandbox->getSnapshot() instanceof Container);
    }

    public function testSetRequest()
    {
        $request = m::mock(Request::class);

        $sandbox = new Sandbox;
        $sandbox->setRequest($request);

        $this->assertSame($request, $sandbox->getRequest());
        $this->assertSame($request, Context::getData('_request'));
    }

    public function testSetSnapshot()
    {
        $container = m::mock(Container::class);

        $sandbox = new Sandbox;
        $sandbox->setSnapshot($container);

        $this->assertSame($container, $sandbox->getSnapshot());
        $this->assertSame($container, Context::getApp());
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
