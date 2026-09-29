<?php

declare(strict_types=1);

namespace Rebet\View\Engine\Blade\Support;

use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Override;
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
     * @param  string $method
     * @return never
     */
    protected function unsupported(string $method)
    {
        throw new LogicException("Application::{$method}() is not supported because this application is a minimal container dedicated to the Blade view engine.");
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function version()
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function basePath($path = '')
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function bootstrapPath($path = '')
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function configPath($path = '')
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function databasePath($path = '')
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function langPath($path = '')
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function publicPath($path = '')
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function resourcePath($path = '')
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function storagePath($path = '')
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     *
     * @param string|array<int, string> ...$environments
     */
    #[Override]
    public function environment(...$environments)
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function runningInConsole()
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function runningUnitTests()
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function hasDebugModeEnabled()
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function maintenanceMode()
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function isDownForMaintenance()
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function registerConfiguredProviders(): void
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function register($provider, $force = false)
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function registerDeferredProvider($provider, $service = null): void
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function resolveProvider($provider)
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function boot(): void
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function booting($callback): void
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function booted($callback): void
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     *
     * @param array<int, string> $bootstrappers
     */
    #[Override]
    public function bootstrapWith(array $bootstrappers): void
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function getLocale()
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function getNamespace()
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     *
     * @return array<int, \Illuminate\Support\ServiceProvider>
     */
    #[Override]
    public function getProviders($provider)
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function hasBeenBootstrapped()
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function loadDeferredProviders(): void
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function setLocale($locale): void
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function shouldSkipMiddleware()
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function terminating($callback)
    {
        $this->unsupported(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function terminate(): void
    {
        $this->unsupported(__FUNCTION__);
    }
}
