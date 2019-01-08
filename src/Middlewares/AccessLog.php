<?php

namespace SwooleTW\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use SwooleTW\Http\Server\AccessOutput;

/**
 * Class AccessLog
 *
 * @codeCoverageIgnore
 */
class AccessLog
{
    /**
     * @var \SwooleTW\Http\Server\AccessOutput
     */
    protected $output;

    /**
     * AccessLog constructor.
     *
     * @param \SwooleTW\Http\Server\AccessOutput $output
     */
    public function __construct(AccessOutput $output)
    {
        $this->output = $output;
    }

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        return $next($request);
    }

    /**
     * Handle the outgoing request and response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Http\Response $response
     */
    public function terminate(Request $request, Response $response)
    {
        $this->output->log($request, $response);
    }
}