<?php

namespace SwooleTW\Http\Concerns;

use Illuminate\Container\Container;
use Illuminate\Contracts\Http\Kernel;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

trait ResetApplication
{
    /**
     * Clear resolved instances.
     */
    public function clearInstances(Container $app)
    {
        $instances = $this->config->get('swoole_http.instances', []);
        foreach ($instances as $instance) {
            $app->forgetInstance($instance);
        }
    }

    /**
     * Bind illuminate request to laravel/lumen application.
     */
    public function bindRequest(Container $app)
    {
        $request = $this->getRequest();
        if ($request instanceof Request) {
            $app->instance('request', $request);
        }
    }

    /**
     * Re-register and reboot service providers.
     */
    public function resetProviders(Container $app)
    {
        foreach ($this->providers as $provider) {
            $this->rebindProviderContainer($app, $provider);
            if (method_exists($provider, 'register')) {
                $provider->register();
            }
            if (method_exists($provider, 'boot')) {
                $app->call([$provider, 'boot']);
            }
        }
    }

    /**
     * Rebind service provider's container.
     */
    protected function rebindProviderContainer($app, $provider)
    {
        $closure = function () use ($app) {
            $this->app = $app;
        };

        $resetProvider = $closure->bindTo($provider, $provider);
        $resetProvider();
    }

    /**
     * Reset laravel/lumen's config to initial values.
     */
    public function resetConfigInstance(Container $app)
    {
        $app->instance('config', clone $this->config);
    }

    /**
     * Reset laravel's session data.
     */
    public function resetSession(Container $app)
    {
        if (isset($app['session'])) {
            $session = $app->make('session');
            $session->flush();
            $session->regenerate();
        }
    }

    /**
     * Reset laravel's cookie.
     */
    public function resetCookie(Container $app)
    {
        if (isset($app['cookie'])) {
            $cookies = $app->make('cookie');
            foreach ($cookies->getQueuedCookies() as $key => $value) {
                $cookies->unqueue($key);
            }
        }
    }

    /**
     * Rebind laravel's container in router.
     */
    public function rebindRouterContainer(Container $app)
    {
        if ($this->isLaravel()) {
            $router = $app->make('router');
            $request = $this->getRequest();
            $closure = function () use ($app, $request) {
                $this->container = $app;
                if (is_null($request)) {
                    return;
                }
                try {
                    $route = $this->routes->match($request);
                    // clear resolved controller
                    if (property_exists($route, 'container')) {
                        $route->controller = null;
                    }
                    // rebind matched route's container
                    $route->setContainer($app);
                } catch (NotFoundHttpException $e) {
                    // do nothing
                }
            };

            $resetRouter = $closure->bindTo($router, $router);
            $resetRouter();
        } else {
            // lumen router only exists after lumen 5.5
            if (property_exists($app, 'router')) {
                $app->router->app = $app;
            }
        }
    }

    /**
     * Rebind laravel/lumen's container in view.
     */
    public function rebindViewContainer(Container $app)
    {
        $view = $app->make('view');

        $closure = function () use ($app) {
            $this->container = $app;
            $this->shared['app'] = $app;
        };

        $resetView = $closure->bindTo($view, $view);
        $resetView();
    }

    /**
     * Rebind laravel's container in kernel.
     */
    public function rebindKernelContainer(Container $app)
    {
        if ($this->isLaravel()) {
            $kernel = $app->make(Kernel::class);

            $closure = function () use ($app) {
                $this->app = $app;
            };

            $resetKernel = $closure->bindTo($kernel, $kernel);
            $resetKernel();
        }
    }
}
