<?php

namespace SwooleTW\Http\Tests\Server;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Illuminate\Http\Request;
use Mockery as m;
use SwooleTW\Http\Coroutine\Context;
use SwooleTW\Http\Exceptions\SandboxException;
use SwooleTW\Http\Server\Application;
use SwooleTW\Http\Server\Resetters\ResetterContract;
use SwooleTW\Http\Server\Sandbox;
use SwooleTW\Http\Tests\TestCase;

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

        $resetter = new TestResetter;
        $resetterName = get_class($resetter);

        $config = m::mock(Repository::class);
        $config->shouldReceive('get')
               ->with('swoole_http.providers', [])
               ->once()
               ->andReturn([$providerName]);
        $config->shouldReceive('get')
               ->with('swoole_http.resetters', [])
               ->once()
               ->andReturn([$resetterName]);

        $container = m::mock(Container::class);
        $container->shouldReceive('make')
                  ->with(\Illuminate\Contracts\Config\Repository::class)
                  ->once()
                  ->andReturn($config);
        $container->shouldReceive('make')
                  ->with($resetterName)
                  ->once()
                  ->andReturn($resetter);

        $sandbox = new Sandbox;
        $sandbox->setBaseApp($container);
        $sandbox->initialize();

        $sandboxProvider = $sandbox->getProviders()[$providerName];
        $sandboxResetter = $sandbox->getResetters()[$resetterName];

        $this->assertTrue($sandbox->getConfig() instanceof Repository);
        $this->assertSame($providerName, get_class($sandboxProvider));

        $this->assertTrue($resetter instanceof ResetterContract);
        $this->assertSame($resetter, $sandboxResetter);
    }

    public function testGetApplication()
    {
        $container = m::mock(Container::class);

        $sandbox = new Sandbox;
        $sandbox->setBaseApp($container);
        $sandbox->getApplication();

        $this->assertTrue($sandbox->getSnapshot() instanceof Container);
    }

    public function testGetCachedSnapshot()
    {
        $container = m::mock(Container::class);
        $snapshot = m::mock(Container::class);
        $snapshot->shouldReceive('offsetGet')
                 ->with('foo')
                 ->once()
                 ->andReturn($result = 'bar');

        $sandbox = new Sandbox;
        $sandbox->setBaseApp($container);
        $sandbox->setSnapshot($snapshot);

        $this->assertTrue($sandbox->getApplication() instanceof Container);
        $this->assertEquals($result, $sandbox->getApplication()->foo);
    }

    public function testRunWithoutSnapshot()
    {
        $this->expectException(SandboxException::class);

        $sandbox = new Sandbox;
        $sandbox->run($request = m::mock(Request::class));
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

    public function testIsLaravel()
    {
        $sandbox = new Sandbox;

        $this->assertTrue($sandbox->isLaravel());
    }

    protected function getContainer()
    {
        $config = m::mock(Illuminate\Config\Repository::class);
        $config->shouldReceive('get')
               ->andReturn([]);
        $container = m::mock(Container::class);
        $container->shouldReceive('offsetGet')
                  ->andReturn((object) []);
        $container->shouldReceive('make')
                  ->andReturn($config);

        return $container;
    }
}

class TestResetter implements ResetterContract
{
    public function handle(ContainerContract $app, Sandbox $sandbox)
    {
        return 'foo';
    }
}
