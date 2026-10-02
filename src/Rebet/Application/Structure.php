<?php

declare(strict_types=1);

namespace Rebet\Application;

use Rebet\Tools\Utility\Path;

/**
 * Application Structure Class
 *
 * Define application structure settings.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Structure
{
    /**
     * The application root directory.
     *
     * @var string
     */
    protected $root;

    /**
     * Create application structure settings.
     *
     * @param string $root
     */
    public function __construct(string $root)
    {
        $this->root = Path::normalize($root);
    }

    /**
     * Get application root path
     *
     * @return string
     */
    public function root(): string
    {
        return $this->root;
    }

    /**
     * Convert application root relative path to absolute path.
     *
     * @param  string|null $relative_path
     * @return string
     */
    public function path(string|null $relative_path): string
    {
        return Path::normalize("{$this->root()}/{$relative_path}");
    }

    /**
     * Get environment file path
     * Defaultly this method return "{Structure::root()}/{$relative_path}", you can override this method if you want.
     *
     * @param  string|null $relative_path (default: null)
     * @return string
     */
    public function env(string|null $relative_path = null): string
    {
        return Path::normalize("{$this->root()}/{$relative_path}");
    }

    /**
     * Get application config path
     * Defaultly this method return "{Structure::root()}/app/config/{$relative_path}", you can override this method if you want.
     *
     * @param  string|null $relative_path (default: null)
     * @return string
     */
    public function config(string|null $relative_path = null): string
    {
        return Path::normalize("{$this->path('/app/config')}/{$relative_path}");
    }

    /**
     * Get application resources path
     * Defaultly this method return "{Structure::root()}/app/resources/{$relative_path}", you can override this method if you want.
     *
     * @param  string|null $relative_path (default: null)
     * @return string
     */
    public function resources(string|null $relative_path = null): string
    {
        return Path::normalize("{$this->path('/app/resources')}/{$relative_path}");
    }

    /**
     * Get application views path
     * Defaultly this method return "{Structure::root()}/app/resources/views/{$relative_path}", you can override this method if you want.
     *
     * @param  string|null $relative_path (default: null)
     * @return string
     */
    public function views(string|null $relative_path = null): string
    {
        return Path::normalize("{$this->resources('/views')}/{$relative_path}");
    }

    /**
     * Get application i18n (internationalization) path
     * Defaultly this method return "{Structure::root()}/app/resources/i18n/{$relative_path}", you can override this method if you want.
     *
     * @param  string|null $relative_path (default: null)
     * @return string
     */
    public function i18n(string|null $relative_path = null): string
    {
        return Path::normalize("{$this->resources('/i18n')}/{$relative_path}");
    }

    /**
     * Get application routes configuration path
     * Defaultly this method return "{Structure::root()}/app/routes/{$relative_path}", you can override this method if you want.
     *
     * @param  string|null $relative_path (default: null)
     * @return string
     */
    public function routes(string|null $relative_path = null): string
    {
        return Path::normalize("{$this->path('/app/routes')}/{$relative_path}");
    }

    /**
     * Get public root path
     * Defaultly this method return "{Structure::root()}/public/{$relative_path}", you can override this method if you want.
     *
     * @param  string|null $relative_path (default: null)
     * @return string
     */
    public function public(string|null $relative_path = null): string
    {
        return Path::normalize("{$this->path('/public')}/{$relative_path}");
    }

    /**
     * Get cache path
     * Defaultly this method return "{Structure::root()}/var/cache/{$relative_path}", you can override this method if you want.
     *
     * @param  string|null $relative_path (default: null)
     * @return string
     */
    public function cache(string|null $relative_path = null): string
    {
        return Path::normalize("{$this->path('/var/cache')}/{$relative_path}");
    }

    /**
     * Get log path
     * Defaultly this method return "{Structure::root()}/var/log/{$relative_path}", you can override this method if you want.
     *
     * @param  string|null $relative_path (default: null)
     * @return string
     */
    public function log(string|null $relative_path = null): string
    {
        return Path::normalize("{$this->path('/var/log')}/{$relative_path}");
    }

    /**
     * Get root storage path.
     * Defaultly this method return "{Structure::root()}/var/storage/{$relative_path}", you can override this method if you want.
     *
     * @param  string|null $relative_path (default: null)
     * @return string
     */
    public function storage(string|null $relative_path = null): string
    {
        return Path::normalize("{$this->path('/var/storage')}/{$relative_path}");
    }

    /**
     * Get private storage path.
     * Defaultly this method return "{Structure::storage()}/private/{$relative_path}", you can override this method if you want.
     *
     * @param  string|null $relative_path (default: null)
     * @return string
     */
    public function privateStorage(string|null $relative_path = null): string
    {
        return Path::normalize("{$this->storage('/private')}/{$relative_path}");
    }

    /**
     * Get public storage path.
     * Defaultly this method return "{Structure::storage()}/public/{$relative_path}", you can override this method if you want.
     *
     * @param  string|null $relative_path (default: null)
     * @return string
     */
    public function publicStorage(string|null $relative_path = null): string
    {
        return Path::normalize("{$this->storage('/public')}/{$relative_path}");
    }

    /**
     * Get root storage url.
     * Defaultly this method return "/storage", you can override this method if you want.
     *
     * @return string
     */
    public function storageUrl(): string
    {
        return "/storage";
    }
}
