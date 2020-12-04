<?php

namespace SwooleTW\Http\Websocket\Middleware;

use Closure;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Arr;

/**
 * Class StartSession
 */
class StartSession
{
    /**
     * The session manager.
     *
     * @var \Illuminate\Session\SessionManager
     */
    protected $manager;

    /**
     * Create a new session middleware.
     *
     * @param  \Illuminate\Session\SessionManager $manager
     *
     * @return void
     */
    public function __construct(SessionManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($this->sessionConfigured()) {
            $request->setLaravelSession($this->startSession($request));
        }

        return $next($request);
    }

    /**
     * Start the session for the given request.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Contracts\Session\Session
     */
    protected function startSession(Request $request)
    {
        return tap($this->getSession($request), function (Session $session) use ($request) {
            $session->setRequestOnHandler($request);

            $session->start();
        });
    }

    /**
     * Get the session implementation from the manager.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Contracts\Session\Session
     */
    public function getSession(Request $request)
    {
        return tap($this->manager->driver(), function (Session $session) use ($request) {
            $session->setId($request->cookies->get($session->getName()));
        });
    }

    /**
     * Determine if a session driver has been configured.
     *
     * @return bool
     */
    protected function sessionConfigured()
    {
        return ! is_null($this->manager->getSessionConfig()['driver'] ?? null);
    }
}
