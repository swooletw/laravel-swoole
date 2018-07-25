<?php

namespace SwooleTW\Http\Concerns;

use Illuminate\Contracts\Http\Kernel;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

trait ResetApplication
{
    /**
     * Re-register and reboot service providers.
     */
    protected function resetProviders($app)
    {
        foreach ($this->providers as $provider) {
            $this->rebindProviderContainer($provider, $app);
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
    protected function rebindProviderContainer($provider, $app)
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
    protected function resetConfigInstance($app)
    {
        $app->instance('config', clone $this->config);
    }

    /**
     * Reset laravel's session data.
     */
    protected function resetSession($app)
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
    protected function resetCookie($app)
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
    protected function rebindRouterContainer($app)
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
    protected function rebindViewContainer($app)
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
    protected function rebindKernelContainer($app)
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
