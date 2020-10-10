<?php

namespace SwooleTW\Http\Websocket\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Support\Facades\Config;

/**
 * Class Authenticate
 */
class Authenticate
{
    /**
     * The authentication factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory|mixed
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory $auth
     *
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     *
     * @return mixed
     *
     */
    public function handle($request, Closure $next)
    {
        try {
            $this->auth->setRequest($request);
            if ($authDriver = Config::get('swoole_websocket.auth_driver')) {
                $this->auth->setDefaultDriver($authDriver);
            }
            if ($user = $this->auth->authenticate()) {
                $request->setUserResolver(function () use ($user) {
                    return $user;
                });
            }
        } catch (AuthenticationException $e) {
            // do nothing
        }

        return $next($request);
    }
}
