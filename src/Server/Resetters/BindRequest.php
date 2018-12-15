<?php

namespace SwooleTW\Http\Server\Resetters;


use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use SwooleTW\Http\Server\Sandbox;

class BindRequest implements ResetterContract
{
    /**
     * "handle" function for resetting app.
     *
     * @param \Illuminate\Contracts\Container\Container $app
     * @param \SwooleTW\Http\Server\Sandbox $sandbox
     */
    public function handle(Container $app, Sandbox $sandbox)
    {
        $request = $sandbox->getRequest();

        if ($request instanceof Request) {
            $app->instance('request', $request);
        }

        return $app;
    }
}
