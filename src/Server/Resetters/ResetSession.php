<?php

namespace SwooleTW\Http\Server\Resetters;

use SwooleTW\Http\Server\Sandbox;
use Illuminate\Contracts\Container\Container;
use SwooleTW\Http\Server\Resetters\ResetterContract;

class ResetSession implements ResetterContract
{
    /**
     * "handle" function for resetting app.
     *
     * @param \Illuminate\Contracts\Container\Container $app
     * @param \SwooleTW\Http\Server\Sandbox $sandbox
     */
    public function handle(Container $app, Sandbox $sandbox)
    {
        if (isset($app['session'])) {
            $session = $app->make('session');
            $session->flush();
            $session->regenerate();
        }

        return $app;
    }
}
