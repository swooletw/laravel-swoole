<?php

namespace SwooleTW\Http\Server;

use DeepCopy\DeepCopy;
use Illuminate\Container\Container;
use DeepCopy\TypeMatcher\TypeMatcher;
use SwooleTW\Http\Server\Application;
use Illuminate\Support\Facades\Facade;
use DeepCopy\TypeFilter\ShallowCopyFilter;

class Sandbox
{
    /**
     * @var \SwooleTW\Http\Server\Application
     */
    protected $application;

    /**
     * @var \SwooleTW\Http\Server\Application
     */
    protected $snapshot;

    /**
     * @var \DeepCopy\TypeFilter\ShallowCopyFilter
     */
    protected $shollowFilter;

    /**
     * @var array
     */
    protected $matchers = [];

    /**
     * @var array
     */
    protected $whitelist = [
        \Closure::class,
        \Illuminate\Foundation\PackageManifest::class,
        \Illuminate\Filesystem\Filesystem::class,
        \Illuminate\Events\Dispatcher::class,
        \Illuminate\Routing\Router::class,
        \Illuminate\Routing\RouteCollection::class,
        \Illuminate\Routing\Route::class,
        \App\Http\Kernel::class,
        \Illuminate\Config\Repository::class,
        \Symfony\Component\HttpFoundation\ParameterBag::class,
        \Symfony\Component\HttpFoundation\ServerBag::class,
        \Symfony\Component\HttpFoundation\FileBag::class,
        \Symfony\Component\HttpFoundation\HeaderBag::class,
        \Illuminate\Database\Connectors\ConnectionFactory::class,
        \Illuminate\Database\DatabaseManager::class,
        \Illuminate\View\Engines\EngineResolver::class,
        \Illuminate\View\Factory::class,
        \Illuminate\View\FileViewFinder::class,
        \Illuminate\Auth\Access\Gate::class,
        \Illuminate\Routing\UrlGenerator::class,
        \Swoole\Table::class,
        \SwooleTW\Http\Websocket\Rooms\RoomContract::class,
        \Illuminate\Support\ServiceProvider::class,
        \Illuminate\Session\Store::class,
        \Illuminate\Session\SessionHandlerInterface::class,
        \Illuminate\Routing\ControllerDispatcher::class,
        \Illuminate\Contracts\Encryption\Encrypter::class,
        \Illuminate\Session\SessionManager::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\Cookie\CookieJar::class,
        \Laravel\Lumen\Routing\Router::class,
        \Illuminate\View\Compilers\BladeCompiler::class,
    ];

    /**
     * Make a sandbox.
     *
     * @param \SwooleTW\Http\Server\Application $application
     * @return \SwooleTW\Http\Server\Sandbox
     */
    public static function make(Application $application)
    {
        return new static($application);
    }

    /**
     * Sandbox constructor.
     *
     * @param \SwooleTW\Http\Server\Application $application
     */
    public function __construct($application)
    {
        $this->application = $application;
        $this->setMatchers();
    }

    /**
     * Set filter and matchers.
     */
    protected function setMatchers()
    {
        $this->shollowFilter = new ShallowCopyFilter;

        foreach ($this->whitelist as $value) {
            $this->matchers[] = new TypeMatcher($value);
        }
    }

    /**
     * Get an application snapshot
     *
     * @return \SwooleTW\Http\Server\Application
     */
    public function getApplication()
    {
        if ($this->snapshot instanceOf Application) {
            return $this->snapshot;
        }

        $deepCopy = $this->getDeepCopy();

        return $this->snapshot = $deepCopy->copy($this->application);
    }

    /**
     * Get a deepCopy instance
     *
     * @return \DeepCopy\TypeFilter\ShallowCopyFilter
     */
    public function getDeepCopy()
    {
        $deepCopy = new DeepCopy;

        foreach ($this->matchers as $matcher) {
            $deepCopy->addTypeFilter($this->shollowFilter, $matcher);
        }

        return $deepCopy;
    }

    /**
     * Get a laravel snapshot
     *
     * @return \Illuminate\Container\Container
     */
    public function getLaravelApp()
    {
        if ($this->snapshot instanceOf Application) {
            return $this->snapshot->getApplication();
        }

        return $this->getApplication()->getApplication();
    }

    /**
     * Set laravel snapshot to container and facade.
     */
    public function enable()
    {
        if (! $this->snapshot instanceOf Application) {
            $this->getApplication($this->application);
        }

        $application = $this->getLaravelApp();

        Container::setInstance($application);
        Facade::setFacadeApplication($application);
    }

    /**
     * Set original laravel app to container and facade.
     */
    public function disable()
    {
        if ($this->snapshot instanceOf Application) {
            $this->snapshot = null;
        }

        $application = $this->application->getApplication();

        Container::setInstance($application);
        Facade::setFacadeApplication($application);
    }
}
