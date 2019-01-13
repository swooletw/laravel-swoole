<?php

namespace SwooleTW\Http\Server\Resetters;

use Illuminate\Contracts\Container\Container;
use SwooleTW\Http\Server\Sandbox;

class ResetProviders implements ResetterContract
{
    /**
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $app;

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
        foreach ($sandbox->getProviders() as $provider) {
            $this->rebindProviderContainer($app, $provider);
            if (method_exists($provider, 'register')) {
                $provider->register();
            }
            if (method_exists($provider, 'boot')) {
                $app->call([$provider, 'boot']);
            }
        }

        return $app;
    }

    /**
     * Rebind service provider's container.
     *
     * @param $app
     * @param $provider
     */
    protected function rebindProviderContainer($app, $provider)
    {
        $closure = function () use ($app) {
            $this->app = $app;
        };

        $resetProvider = $closure->bindTo($provider, $provider);
        $resetProvider();
    }
}
