<?php

namespace SwooleTW\Http\Server\Resetters;


use Illuminate\Contracts\Container\Container;
use SwooleTW\Http\Server\Sandbox;

class ResetConfig implements ResetterContract
{
    /**
     * "handle" function for resetting app.
     *
     * @param \Illuminate\Contracts\Container\Container $app
     * @param \SwooleTW\Http\Server\Sandbox $sandbox
     */
    public function handle(Container $app, Sandbox $sandbox)
    {
        $app->instance('config', clone $sandbox->getConfig());

        return $app;
    }
}
