<?php
namespace Rebet\Application;

use Rebet\Common\Path;

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
    public function root() : string
    {
        return $this->root;
    }

    /**
     * Convert application root relative path to absolute path.
     *
     * @param string|null $relative_path
     * @return string
     */
    public function path(?string $relative_path) : string
    {
        return Path::normalize("{$this->root()}/{$relative_path}");
    }

    /**
     * Get environment file path
     *
     * @param string|null $relative_path (default: null)
     * @return string
     */
    public function env(?string $relative_path = null) : string
    {
        return Path::normalize("{$this->root()}/{$relative_path}");
    }

    /**
     * Get application bootstrap modules path
     *
     * @param string|null $relative_path (default: null)
     * @return string
     */
    public function bootstrap(?string $relative_path = null) : string
    {
        return Path::normalize("{$this->path('/app/bootstrap')}/{$relative_path}");
    }

    /**
     * Get application config path
     *
     * @param string|null $relative_path (default: null)
     * @return string
     */
    public function config(?string $relative_path = null) : string
    {
        return Path::normalize("{$this->path('/app/configs')}/{$relative_path}");
    }

    /**
     * Get application resources path
     *
     * @param string|null $relative_path (default: null)
     * @return string
     */
    public function resources(?string $relative_path = null) : string
    {
        return Path::normalize("{$this->path('/app/resources')}/{$relative_path}");
    }

    /**
     * Get application routes configuration path
     *
     * @param string|null $relative_path (default: null)
     * @return string
     */
    public function routes(?string $relative_path = null) : string
    {
        return Path::normalize("{$this->path('/app/routes')}/{$relative_path}");
    }

    /**
     * Get application views path
     *
     * @param string|null $relative_path (default: null)
     * @return string
     */
    public function views(?string $relative_path = null) : string
    {
        return Path::normalize("{$this->path('/app/views')}/{$relative_path}");
    }

    /**
     * Get public root path
     *
     * @param string|null $relative_path (default: null)
     * @return string
     */
    public function public(?string $relative_path = null) : string
    {
        return Path::normalize("{$this->path('/public')}/{$relative_path}");
    }

    /**
     * Get cache path
     *
     * @param string|null $relative_path (default: null)
     * @return string
     */
    public function cache(?string $relative_path = null) : string
    {
        return Path::normalize("{$this->path('/var/cache')}/{$relative_path}");
    }

    /**
     * Get logs path
     *
     * @param string|null $relative_path (default: null)
     * @return string
     */
    public function logs(?string $relative_path = null) : string
    {
        return Path::normalize("{$this->path('/var/logs')}/{$relative_path}");
    }

    /**
     * Get root storage path.
     *
     * @param string|null $relative_path (default: null)
     * @return string
     */
    public function storage(?string $relative_path = null) : string
    {
        return Path::normalize("{$this->path('/var/storage')}/{$relative_path}");
    }

    /**
     * Get private storage path.
     *
     * @param string|null $relative_path (default: null)
     * @return string
     */
    public function privateStorage(?string $relative_path = null) : string
    {
        return Path::normalize("{$this->storage('/private')}/{$relative_path}");
    }

    /**
     * Get public storage path.
     *
     * @param string|null $relative_path (default: null)
     * @return string
     */
    public function publicStorage(?string $relative_path = null) : string
    {
        return Path::normalize("{$this->storage('/public')}/{$relative_path}");
    }

    /**
     * Get root storage url.
     *
     * @return string
     */
    public function storageUrl() : string
    {
        return "/storage";
    }
}