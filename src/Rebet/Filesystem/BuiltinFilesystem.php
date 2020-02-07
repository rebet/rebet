<?php
namespace Rebet\Filesystem;

use League\Flysystem\Adapter\AbstractFtpAdapter;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AdapterInterface;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\Config;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem as FlysystemFilesystem;
use League\Flysystem\FilesystemInterface;
use Rebet\Common\Exception\RuntimeException;
use Rebet\Common\Path;
use Rebet\Config\Configurable;

/**
 * Builtin Filesystem Class
 *
 * @see https://github.com/laravel/framework/blob/v6.13.1/src/Illuminate/Filesystem/FilesystemAdapter.php
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class BuiltinFilesystem implements Filesystem
{
    use Configurable;

    public static function defaultConfig()
    {
        return [
            'driver' => FlysystemFilesystem::class
        ];
    }

    /**
     * Filesystem driver
     *
     * @var FilesystemInterface
     */
    protected $driver;

    /**
     * {@inheritDoc}
     */
    public function __construct(AdapterInterface $adapter, $config = null)
    {
        $driver       = static::config('driver');
        $this->driver = new $driver($adapter, $config);
    }

    /**
     * {@inheritDoc}
     */
    public function url(string $path) : string
    {
        if (!$this->getVisibility($path)) {
            throw new FileNotFoundException($path);
        }

        $adapter = $this->adapter instanceof CachedAdapter ? $this->adapter->getAdapter() : $this->adapter ;
        if (method_exists($adapter, 'getUrl')) {
            return $adapter->getUrl($path);
        }
        if ($adapter instanceof AwsS3Adapter) {
            if (! is_null($url = $this->getConfig()->get('url'))) {
                return Path::normalize($url.'/'.$adapter->getPathPrefix().$path);
            }
            return $adapter->getClient()->getObjectUrl($adapter->getBucket(), $adapter->getPathPrefix().$path);
        }
        if ($adapter instanceof AbstractFtpAdapter || $adapter instanceof Local) {
            $url = $this->getConfig()->get('url');
            return $url ? Path::normalize($url.'/'.$path) : $path ;
        }
        throw new RuntimeException('This adapter does not support retrieving URLs.');
    }
}
