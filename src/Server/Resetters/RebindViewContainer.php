<?php

namespace SwooleTW\Http\Server\Resetters;

use SwooleTW\Http\Server\Sandbox;
use Illuminate\Contracts\Container\Container;

class RebindViewContainer
{
    /**
     * "handle" function for resetting app.
     *
     * @param \Illuminate\Contracts\Container\Container $app
     * @param \SwooleTW\Http\Server\Sandbox $sandbox
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
