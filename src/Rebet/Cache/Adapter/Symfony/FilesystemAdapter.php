<?php
namespace Rebet\Cache\Adapter\Symfony;

use Rebet\Tools\Math\Unit;
use Symfony\Component\Cache\Adapter\FilesystemAdapter as SymfonyFilesystemAdapter;
use Symfony\Component\Cache\Marshaller\MarshallerInterface;

/**
 * Filesystem Adapter Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class FilesystemAdapter extends AbstractSymfonyAdapter
{
    /**
     * Create Filesystem Adapter
     *
     * @param string $namespace (default: '')
     * @param int|string $default_lifetime that time unit labeled string like '12min', or int seconds. (default: 0)
     * @param string|null $directory (default: false)
     * @param MarshallerInterface|null $marshaller (default: mull)
     * @param bool $taggable (default: false)
     * @param null|string|CacheItemPoolInterface $tags_pool name that `Cache.stores.{name}` or CacheItemPoolInterface instance when taggable is true. (default: null for use given $adapter as it is)
     * @param float $known_tag_versions_ttl when taggable is true. (default: 0.15)
     */
    public function __construct(string $namespace = '', $default_lifetime = 0, ?string $directory = null, ?MarshallerInterface $marshaller = null, bool $taggable = false, $tags_pool = null, $known_tag_versions_ttl = 0.15)
    {
        parent::__construct(
            new SymfonyFilesystemAdapter(
                $namespace,
                Unit::of(Unit::TIME)->convert($default_lifetime)->toInt(),
                $directory,
                $marshaller
            ),
            $taggable,
            $tags_pool,
            $known_tag_versions_ttl
        );
    }
}
