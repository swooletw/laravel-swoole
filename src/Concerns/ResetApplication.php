<?php

namespace SwooleTW\Http\Concerns;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use SwooleTW\Http\Exceptions\SandboxException;
use SwooleTW\Http\Server\Resetters\ResetterContract;

trait ResetApplication
{
    /**
     * @var \Illuminate\Config\Repository
     */
    protected $config;

    /**
     * @var array
     */
    protected $providers = [];

    /**
     * @var \SwooleTW\Http\Server\Resetters\ResetterContract[]|array
     */
    protected $resetters = [];

    /**
     * Set initial config.
     */
    protected function setInitialConfig()
    {
        $this->config = clone $this->getBaseApp()->make(Repository::class);
    }

    /**
     * Get config snapshot.
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Initialize customized service providers.
     */
    protected function setInitialProviders()
    {
        $app = $this->getBaseApp();
        $providers = $this->config->get('swoole_http.providers', []);

        foreach ($providers as $provider) {
            if (class_exists($provider) && ! in_array($provider, $this->providers)) {
                $providerClass = new $provider($app);
                $this->providers[$provider] = $providerClass;
            }
        }
    }

    /**
     * Get Initialized providers.
     */
    public function getProviders()
    {
        return $this->providers;
    }

    /**
     * Initialize resetters.
     */
    protected function setInitialResetters()
    {
        $app = $this->getBaseApp();
        $resetters = $this->config->get('swoole_http.resetters', []);

        foreach ($resetters as $resetter) {
            $resetterClass = $app->make($resetter);
            if (! $resetterClass instanceof ResetterContract) {
                throw new SandboxException("{$resetter} must implement " . ResetterContract::class);
            }
            $this->resetters[$resetter] = $resetterClass;
        }
    }

    /**
     * Get Initialized resetters.
     */
    public function getResetters()
    {
        return $this->resetters;
    }

    /**
     * Reset Laravel/Lumen Application.
     *
     * @param \Illuminate\Contracts\Container\Container $app
     */
    public function resetApp(Container $app)
    {
        foreach ($this->resetters as $resetter) {
            $resetter->handle($app, $this);
        }
    }
}
