<?php

namespace SwooleTW\Http\Tests\Server\Resetters;

use Mockery as m;
use Illuminate\Http\Request;
use SwooleTW\Http\Tests\TestCase;
use SwooleTW\Http\Server\Sandbox;
use Illuminate\Container\Container;
use SwooleTW\Http\Server\Resetters\RebindRouterContainer;

class RebindRouterContainerTest extends TestCase
{
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

        $this->mockMethod('property_exists', function () {
            return true;
        }, 'SwooleTW\Http\Server\Resetters');

        $resetter = new RebindRouterContainer;
        $app = $resetter->handle($container, $sandbox);

        $this->assertSame($app, $app->router->app);
    }
}
