<?php

namespace SwooleTW\Http\Server\Resetters;

use SwooleTW\Http\Server\Sandbox;
use Illuminate\Contracts\Container\Container;

class ResetConfig implements ResetterContract
{
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
        $app->instance('config', clone $sandbox->getConfig());

        return $app;
    }
}
