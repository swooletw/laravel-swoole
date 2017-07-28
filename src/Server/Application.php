<?php

/*
 * This file is part of the huang-yi/laravel-swoole-http package.
 *
 * (c) Huang Yi <coodeer@163.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HuangYi\Http\Server;

use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

class Application
{
    /**
     * Current framework.
     *
     * @var string
     */
    protected $framework;

    /**
     * Laravel|Lumen Application.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $application;

    /**
     * Make an application.
     *
     * @param $framework
     * @return \HuangYi\Http\Server\Application
     */
    public static function make($framework)
    {
        return new static($framework);
    }

    /**
     * Application constructor.
     *
     * @param string $framework
     */
    public function __construct($framework)
    {
        $this->framework = $framework;

        $this->bootstrap();
    }

    /**
     * Bootstrap framework.
     */
    protected function bootstrap()
    {
        $this->loadApplication();

        if ($this->framework == 'laravel') {
            $bootstrappers = $this->getBootstrappers();
            $this->application->bootstrapWith($bootstrappers);
        }
    }

    /**
     * Load application.
     *
     * @return \Illuminate\Contracts\Foundation\Application
     */
    protected function loadApplication()
    {
        if (! $this->application instanceof ApplicationContract) {
            $this->application = require base_path() . '/bootstrap/app.php';
        }

        return $this->application;
    }

    /**
     * Run framework.
     *
     * @param \Illuminate\Http\Request $request
     * @return mixed
     */
    public function run(Request $request)
    {
        $method = sprintf('run%s', ucfirst($this->framework));

        return $this->$method($request);
    }

    /**
     * Run Laravel framework.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    protected function runLaravel(Request $request)
    {
        $kernel = $this->loadApplication()->make(Kernel::class);

        return $kernel->handle($request);
    }

    /**
     * Run lumen framework.
     *
     * @param \Illuminate\Http\Request $request
     * @return mixed
     */
    protected function runLumen(Request $request)
    {
        $application = $this->loadApplication();

        return $application->dispatch($request);
    }

    /**
     * Get bootstrappers.
     *
     * @return array
     */
    protected function getBootstrappers()
    {
        $kernel = $this->loadApplication()->make(Kernel::class);

        // Reflect Kernel
        $reflection = new \ReflectionObject($kernel);

        $bootstrappersMethod = $reflection->getMethod('bootstrappers');
        $bootstrappersMethod->setAccessible(true);

        $bootstrappers = $bootstrappersMethod->invoke($kernel);

        array_splice($bootstrappers, -2, 0, ['Illuminate\Foundation\Bootstrap\SetRequestForConsole']);

        return $bootstrappers;
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Http\Response $response
     */
    public function terminate(Request $request, $response)
    {
        $method = sprintf('terminate%s', ucfirst($this->framework));

        $this->$method($request, $response);
    }

    /**
     * Terminate Laravel framework.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Http\Response $response
     */
    protected function terminateLaravel(Request $request, $response)
    {
        $this->loadApplication()->make(Kernel::class)->terminate($request, $response);
    }

    /**
     * Terminate Lumen framework.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Http\Response $response
     */
    protected function terminateLumen(Request $request, $response)
    {
        $application = $this->loadApplication();

        // Reflections
        $reflection = new \ReflectionObject($application);

        $middleware = $reflection->getProperty('middleware');
        $middleware->setAccessible(true);

        $callTerminableMiddleware = $reflection->getMethod('callTerminableMiddleware');
        $callTerminableMiddleware->setAccessible(true);

        if (count($middleware->getValue($application)) > 0) {
            $callTerminableMiddleware->invoke($application, $response);
        }
    }
}
