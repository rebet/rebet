<?php
declare(strict_types=1);

namespace Rebet\View\Engine\Blade\Support;

use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Rebet\Tools\Exception\LogicException;

/**
 * Blade View Engine Application Class
 *
 * Illuminate\Support\Facades\Facade::setFacadeApplication() requires an
 * Illuminate\Contracts\Foundation\Application, but Rebet\View\Engine\Blade\Blade only depends
 * on illuminate/view (and its illuminate/container dependency), not the full illuminate/foundation
 * package, so no real Application implementation is available/needed. This class satisfies that
 * type by extending the bare Container and stubbing out the framework-lifecycle members of
 * Application (service providers, boot/terminate hooks, path resolution, ...) that resolving
 * Blade facades (e.g. `\Illuminate\Support\Facades\Blade::execute()` used by compiled templates)
 * never touches.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 *
 * @see \Rebet\View\Engine\Blade\Blade::__construct()
 */
class Application extends Container implements ApplicationContract
{
    /**
     * @param string $method
     * @return never
     */
    protected function unsupported(string $method)
    {
        throw new LogicException("Application::{$method}() is not supported because this application is a minimal container dedicated to the Blade view engine.");
    }

    /**
     * {@inheritDoc}
     */
    public function version()
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    public function basePath($path = '')
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    public function bootstrapPath($path = '')
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    public function configPath($path = '')
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    public function databasePath($path = '')
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    public function langPath($path = '')
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    public function publicPath($path = '')
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    public function resourcePath($path = '')
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    public function storagePath($path = '')
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     *
     * @param string|array<int, string> ...$environments
     */
    public function environment(...$environments)
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    public function runningInConsole()
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    public function runningUnitTests()
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    public function hasDebugModeEnabled()
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    public function maintenanceMode()
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    public function isDownForMaintenance()
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    public function registerConfiguredProviders()
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    public function register($provider, $force = false)
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    public function registerDeferredProvider($provider, $service = null)
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    public function resolveProvider($provider)
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    public function boot()
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    public function booting($callback)
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    public function booted($callback)
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     *
     * @param array<int, string> $bootstrappers
     */
    public function bootstrapWith(array $bootstrappers)
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    public function getLocale()
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    public function getNamespace()
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     *
     * @return array<int, \Illuminate\Support\ServiceProvider>
     */
    public function getProviders($provider)
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    public function hasBeenBootstrapped()
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    public function loadDeferredProviders()
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    public function setLocale($locale)
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    public function shouldSkipMiddleware()
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    public function terminating($callback)
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    public function terminate()
    {
        $this->unsupported(__FUNCTION__);
    }
}
