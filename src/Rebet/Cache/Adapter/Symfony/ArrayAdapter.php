<?php
namespace Rebet\Cache\Adapter\Symfony;

use Rebet\Cache\Adapter\Symfony\Pool\ArrayPool;
use Rebet\Common\Unit;
use Symfony\Component\Cache\Adapter\ProxyAdapter;

/**
 * Array Adapter Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class ArrayAdapter extends AbstractSymfonyAdapter
{
    /**
     * Create Array Adapter
     *
     * @param string $namespace (default:'')
     * @param int|string $default_lifetime that time unit labeled string like '12min', or int seconds. (default: 0)
     * @param bool $taggable (default: false)
     * @param null|string|CacheItemPoolInterface $tags_pool name that `Cache.stores.{name}` or CacheItemPoolInterface instance when taggable is true. (default: null for use given $adapter as it is)
     * @param float $known_tag_versions_ttl when taggable is true. (default: 0.15)
     */
    public function __construct(string $namespace = '', $default_lifetime = 0, bool $taggable = false, $tags_pool = null, $known_tag_versions_ttl = 0.15)
    {
        parent::__construct(
            new ProxyAdapter(
                new ArrayPool(), // @todo Symfony ver 4.4.11 ArrayAdapter has problem #37667, if fixed version released then change to use ArrayAdapter.
                $namespace,
                Unit::of(Unit::TIME)->convert($default_lifetime)->toInt()
            ),
            $taggable,
            $tags_pool,
            $known_tag_versions_ttl
        );
    }
}
