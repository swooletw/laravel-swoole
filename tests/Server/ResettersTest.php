<?php

namespace SwooleTW\Http\Tests\Server;

use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Mockery as m;
use SwooleTW\Http\Server\Resetters\BindRequest;
use SwooleTW\Http\Server\Resetters\ClearInstances;
use SwooleTW\Http\Server\Resetters\RebindKernelContainer;
use SwooleTW\Http\Server\Resetters\RebindRouterContainer;
use SwooleTW\Http\Server\Resetters\RebindViewContainer;
use SwooleTW\Http\Server\Resetters\ResetConfig;
use SwooleTW\Http\Server\Resetters\ResetCookie;
use SwooleTW\Http\Server\Resetters\ResetProviders;
use SwooleTW\Http\Server\Resetters\ResetSession;
use SwooleTW\Http\Server\Sandbox;
use SwooleTW\Http\Tests\TestCase;

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

        $this->mockMethod('property_exists', function () {
            return true;
        }, 'SwooleTW\Http\Server\Resetters');

        $resetter = new RebindRouterContainer;
        $app = $resetter->handle($container, $sandbox);

        $this->assertSame($app, $app->router->app);
    }

    public function testRebindViewContainer()
    {
        $sandbox = m::mock(Sandbox::class);
        $view = m::mock('view');

        $container = new Container;
        $container->instance('view', $view);

        $resetter = new RebindViewContainer;
        $app = $resetter->handle($container, $sandbox);

        $this->assertSame($app, $app->make('view')->container);
        $this->assertSame($app, $app->make('view')->shared['app']);
    }

    public function testResetConfig()
    {
        $config = m::mock(ConfigContract::class);
        $sandbox = m::mock(Sandbox::class);
        $sandbox->shouldReceive('getConfig')
                ->once()
                ->andReturn($config);

        $container = new Container;
        $resetter = new ResetConfig;
        $app = $resetter->handle($container, $sandbox);

        $this->assertSame(get_class($config), get_class($app->make('config')));
    }

    public function testResetCookie()
    {
        $cookies = m::mock('cookies');
        $cookies->shouldReceive('getQueuedCookies')
                ->once()
                ->andReturn(['foo']);
        $cookies->shouldReceive('unqueue')
                ->once()
                ->with('foo');

        $sandbox = m::mock(Sandbox::class);

        $container = new Container;
        $container->instance('cookie', $cookies);

        $resetter = new ResetCookie;
        $app = $resetter->handle($container, $sandbox);
    }

    public function testResetSession()
    {
        $session = m::mock('session');
        $session->shouldReceive('flush')
                ->once();
        $session->shouldReceive('regenerate')
                ->once();

        $sandbox = m::mock(Sandbox::class);

        $container = new Container;
        $container->instance('session', $session);

        $resetter = new ResetSession;
        $app = $resetter->handle($container, $sandbox);
    }

    public function testResetProviders()
    {
        $provider = m::mock(TestProvider::class);
        $provider->shouldReceive('register')
                 ->once();
        $provider->shouldReceive('boot')
                 ->once();

        $sandbox = m::mock(Sandbox::class);
        $sandbox->shouldReceive('getProviders')
                ->once()
                ->andReturn([$provider]);

        $this->mockMethod('method_exists', function () {
            return true;
        }, 'SwooleTW\Http\Server\Resetters');

        $container = new Container;
        $resetter = new ResetProviders;
        $app = $resetter->handle($container, $sandbox);

        $reflector = new \ReflectionProperty(TestProvider::class, 'app');
        $reflector->setAccessible(true);

        $this->assertSame($app, $reflector->getValue($provider));
    }
}

class TestProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        //
    }
}
