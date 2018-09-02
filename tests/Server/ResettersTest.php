<?php

namespace SwooleTW\Http\Tests\Server;

use Mockery as m;
use Illuminate\Http\Request;
use SwooleTW\Http\Server\Sandbox;
use SwooleTW\Http\Tests\TestCase;
use Illuminate\Container\Container;
use Illuminate\Contracts\Http\Kernel;
use SwooleTW\Http\Server\Resetters\BindRequest;
use SwooleTW\Http\Server\Resetters\ClearInstances;
use SwooleTW\Http\Server\Resetters\RebindKernelContainer;
use SwooleTW\Http\Server\Resetters\RebindRouterContainer;

class ResettersTest extends TestCase
{
    public function testBindRequest()
    {
        $request = m::mock(Request::class);

        $sandbox = m::mock(Sandbox::class);
        $sandbox->shouldReceive('getRequest')
            ->once()
            ->andReturn($request);

        $container = new Container;

        $resetter = new BindRequest;
        $app = $resetter->handle($container, $sandbox);

        $this->assertSame($request, $app->make('request'));
    }

    public function testClearInstance()
    {
        $sandbox = m::mock(Sandbox::class);
        $sandbox->shouldReceive('getConfig->get')
            ->with('swoole_http.instances', [])
            ->once()
            ->andReturn(['foo']);

        $container = new Container;
        $container->instance('foo', m::mock('foo'));

        $resetter = new ClearInstances;
        $app = $resetter->handle($container, $sandbox);

        $this->assertFalse($app->bound('foo'));
    }

    public function testRebindKernelContainer()
    {
        $sandbox = m::mock(Sandbox::class);
        $sandbox->shouldReceive('isLaravel')
            ->once()
            ->andReturn(true);

        $kernel = m::mock(Kernel::class);

        $container = new Container;
        $container->instance(Kernel::class, $kernel);

        $resetter = new RebindKernelContainer;
        $app = $resetter->handle($container, $sandbox);

        $this->assertSame($app, $app->make(Kernel::class)->app);
    }

    public function testRebindLaravelRouterContainer()
    {
        $request = m::mock(Request::class);

        $sandbox = m::mock(Sandbox::class);
        $sandbox->shouldReceive('isLaravel')
            ->once()
            ->andReturn(true);
        $sandbox->shouldReceive('getRequest')
            ->once()
            ->andReturn($request);

        $router = m::mock('router');

        $container = new Container;
        $container->instance('router', $router);

        $route = m::mock('route');
        $route->controller = 'controller';
        $route->shouldReceive('setContainer')
            ->once()
            ->with($container);

        $routes = m::mock('routes');
        $routes->shouldReceive('match')
            ->once()
            ->with($request)
            ->andReturn($route);

        $router->routes = $routes;

        $resetter = new RebindRouterContainer;
        $app = $resetter->handle($container, $sandbox);

        $this->assertSame($app, $router->container);
    }

    public function testRebindLumenRouterContainer()
    {
        $sandbox = m::mock(Sandbox::class);
        $sandbox->shouldReceive('isLaravel')
            ->once()
            ->andReturn(false);

        $router = m::mock('router');

        $container = m::mock(Container::class);
        $container->shouldReceive('offsetSet')
            ->with('router', $router)
            ->once()
            ->andReturnSelf();
        $container->shouldReceive('offsetGet')
            ->with('router')
            ->andReturn($router);
        $container->router = $router;

        parent::mockMethod('property_exists', function () {
            return true;
        }, 'SwooleTW\Http\Server\Resetters');

        $resetter = new RebindRouterContainer;
        $app = $resetter->handle($container, $sandbox);

        $this->assertSame($app, $app->router->app);
    }
}
