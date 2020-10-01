<?php
namespace Rebet\Cache\Adapter\Symfony;

use Rebet\Cache\Exception\CacheException;
use Rebet\Tools\Unit;
use Symfony\Component\Cache\Adapter\ApcuAdapter as SymfonyApcuAdapter;

/**
 * APCu Adapter Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class ApcuAdapter extends AbstractSymfonyAdapter
{
    /**
     * Create APCu Adapter
     *
     * @param string $namespace (default:'')
     * @param int|string $default_lifetime that time unit labeled string like '12min', or int seconds. (default: 0)
     * @param string $version (default: null)
     * @param bool $taggable (default: false)
     * @param null|string|CacheItemPoolInterface $tags_pool name that `Cache.stores.{name}` or CacheItemPoolInterface instance when taggable is true. (default: null for use given $adapter as it is)
     * @param float $known_tag_versions_ttl when taggable is true. (default: 0.15)
     * @throws CacheException if APCu is not enabled
     */
    public function __construct(string $namespace = '', $default_lifetime = 0, string $version = null, bool $taggable = false, $tags_pool = null, $known_tag_versions_ttl = 0.15)
    {
        if (!static::isSupported()) {
            throw new CacheException("APCu extension is not enabled.");
        }

        parent::__construct(
            new SymfonyApcuAdapter(
                $namespace,
                Unit::of(Unit::TIME)->convert($default_lifetime)->toInt(),
                $version
            ),
            $taggable,
            $tags_pool,
            $known_tag_versions_ttl
        );
    }

    /**
     * It checks apuc extension was supported or not.
     *
     * @return bool
     */
    public static function isSupported()
    {
        return SymfonyApcuAdapter::isSupported();
    }
}
