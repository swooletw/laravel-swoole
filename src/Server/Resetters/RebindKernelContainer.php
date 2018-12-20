<?php

namespace SwooleTW\Http\Server\Resetters;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Http\Kernel;
use SwooleTW\Http\Server\Sandbox;

class RebindKernelContainer implements ResetterContract
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
        if ($sandbox->isLaravel()) {
            $kernel = $app->make(Kernel::class);

            $closure = function () use ($app) {
                $this->app = $app;
            };

            $resetKernel = $closure->bindTo($kernel, $kernel);
            $resetKernel();
        }

        return $app;
    }
}
