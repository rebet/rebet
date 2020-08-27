<?php
namespace Rebet\Cache\Adapter\Symfony;

use Rebet\Cache\Exception\CacheException;
use Rebet\Common\Unit;
use Symfony\Component\Cache\Adapter\MemcachedAdapter as SymfonyMemcachedAdapter;
use Symfony\Component\Cache\Marshaller\MarshallerInterface;

/**
 * Memcached Adapter Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class MemcachedAdapter extends AbstractSymfonyAdapter
{
    /**
     * Create Memcached Adapter
     *
     * @param array[]|string|string[] $dsn An array of dsns, a DSN, or an array of DSNs
     * @param array $options for connect memcached. (default: [])
     * @param string $namespace (default: '')
     * @param int|string $default_lifetime that time unit labeled string like '12min', or int seconds. (default: 0)
     * @param MarshallerInterface|null $marshaller (default: mull)
     * @param bool $taggable (default: false)
     * @param null|string|CacheItemPoolInterface $tags_pool name that `Cache.stores.{name}` or CacheItemPoolInterface instance when taggable is true. (default: null for use given $adapter as it is)
     * @param float $known_tag_versions_ttl when taggable is true. (default: 0.15)
     */
    public function __construct($dsn, array $options = [], string $namespace = '', $default_lifetime = 0, MarshallerInterface $marshaller = null, bool $taggable = false, $tags_pool = null, $known_tag_versions_ttl = 0.15)
    {
        if (!static::isSupported()) {
            throw new CacheException("Memcached extension is not enabled.");
        }

        parent::__construct(
            new SymfonyMemcachedAdapter(
                SymfonyMemcachedAdapter::createConnection($dsn, $options),
                $namespace,
                Unit::of(Unit::TIME)->convert($default_lifetime)->toInt(),
                $marshaller
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
        return SymfonyMemcachedAdapter::isSupported();
    }
}
