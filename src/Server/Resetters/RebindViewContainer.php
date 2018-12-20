<?php

namespace SwooleTW\Http\Server\Resetters;

use Illuminate\Contracts\Container\Container;
use SwooleTW\Http\Server\Sandbox;

class RebindViewContainer implements ResetterContract
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var array
     */
    protected $shared;

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
        $view = $app->make('view');

        $closure = function () use ($app) {
            $this->container = $app;
            $this->shared['app'] = $app;
        };

        $resetView = $closure->bindTo($view, $view);
        $resetView();

        return $app;
    }
}
