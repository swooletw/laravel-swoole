<?php

namespace SwooleTW\Http\Server\Resetters;

use Illuminate\Contracts\Container\Container;
use SwooleTW\Http\Server\Sandbox;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RebindRouterContainer implements ResetterContract
{
    /**
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * @var mixed
     */
    protected $routes;

    /**
     * "handle" function for resetting app.
     *
     * @param \Illuminate\Contracts\Container\Container $app
     * @param \SwooleTW\Http\Server\Sandbox $sandbox
     *
     * @return \Illuminate\Contracts\Container\Container
     */
    public function handle(Container $app, Sandbox $sandbox)
    {
        if ($sandbox->isLaravel()) {
            $router = $app->make('router');
            $request = $sandbox->getRequest();
            $closure = function () use ($app, $request) {
                $this->container = $app;
                if (is_null($request)) {
                    return;
                }
                try {
                    /** @var mixed $route */
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

        return $app;
    }
}
