<?php

namespace SwooleTW\Http\Server\Resetters;

use SwooleTW\Http\Server\Sandbox;
use Illuminate\Contracts\Container\Containerr;

class ResetConfig
{
    /**
     * "handle" function for resetting app.
     *
     * @param \Illuminate\Contracts\Container\Containerr $app
     * @param \SwooleTW\Http\Server\Sandbox $sandbox
     */
    public function handle(Container $app, Sandbox $sandbox)
    {
        $app->instance('config', clone $sandbox->getConfig());
    }
}
