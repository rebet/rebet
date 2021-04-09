<?php
namespace Rebet\Cache\Adapter\Symfony;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Rebet\Cache\Adapter\Adapter;
use Rebet\Cache\Cache;
use Rebet\Cache\Exception\UnsupportedTaggingException;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ProxyAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\PruneableInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Abstract Symfony Adapter Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
abstract class AbstractSymfonyAdapter implements Adapter
{
    /**
     * @var AdapterInterface|TagAwareAdapterInterface of cache pool
     */
    protected $pool;

    /**
     * Create Adapter depend on symfony/cache modules.
     *
     * @param AdapterInterface $adapter
     * @param bool $taggable (default: false)
     * @param null|string|CacheItemPoolInterface $tags_pool name that `Cache.stores.{name}` or CacheItemPoolInterface instance when taggable is true. (default: null for use given $adapter as it is)
     * @param float $known_tag_versions_ttl when taggable is true. (default: 0.15)
     */
    public function __construct(AdapterInterface $adapter, bool $taggable = false, $tags_pool = null, float $known_tag_versions_ttl = 0.15)
    {
        switch (true) {
            case $tags_pool === null:
                $tags_pool = $adapter;
            break;
            case $tags_pool instanceof CacheItemPoolInterface:
                $tags_pool = new ProxyAdapter($tags_pool);
            break;
            case is_string($tags_pool):
                $tags_pool = new ProxyAdapter(Cache::store($tags_pool)->adapter());
            break;
        }
        if ($taggable) {
            $this->pool =
                // PdoAdapter for pgsql can not contains '\0' for cache_item_id, so change TAGS_PREFIX.
                new class($adapter, $tags_pool, $known_tag_versions_ttl) extends TagAwareAdapter {
                    const TAGS_PREFIX = " [tags] ";
                };
        } else {
            $this->pool = $adapter;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getItem($key)
    {
        return $this->pool->getItem($key);
    }

    /**
     * {@inheritDoc}
     */
    public function getItems(array $keys = [])
    {
        return $this->pool->getItems($keys);
    }

    /**
     * {@inheritDoc}
     */
    public function hasItem($key)
    {
        return $this->pool->hasItem($key);
    }

    /**
     * {@inheritDoc}
     */
    public function clear()
    {
        return $this->pool->clear();
    }

    /**
     * {@inheritDoc}
     */
    public function deleteItem($key)
    {
        return $this->pool->deleteItem($key);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteItems(array $keys)
    {
        return $this->pool->deleteItems($keys);
    }

    /**
     * {@inheritDoc}
     */
    public function save(CacheItemInterface $item)
    {
        return $this->pool->save($item);
    }

    /**
     * {@inheritDoc}
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        return $this->pool->saveDeferred($item);
    }

    /**
     * {@inheritDoc}
     */
    public function commit()
    {
        return $this->pool->commit();
    }

    /**
     * {@inheritDoc}
     */
    public function taggable() : bool
    {
        return $this->pool instanceof TagAwareAdapterInterface;
    }

    /**
     * {@inheritDoc}
     */
    public function saveWithTags(CacheItemInterface $item, array $tags = [])
    {
        if (empty($tags)) {
            return $this->save($item);
        }
        if ($this->taggable() && $item instanceof ItemInterface) {
            return $this->save($item->tag($tags));
        }

        throw new UnsupportedTaggingException("This adapter does not support tagging.");
    }

    /**
     * {@inheritDoc}
     */
    public function saveDeferredWithTags(CacheItemInterface $item, array $tags = [])
    {
        if (empty($tags)) {
            return $this->saveDeferred($item);
        }
        if ($this->taggable() && $item instanceof ItemInterface) {
            return $this->saveDeferred($item->tag($tags));
        }

        throw new UnsupportedTaggingException("This adapter does not support tagging.");
    }

    /**
     * {@inheritDoc}
     */
    public function clearByTags(array $tags) : bool
    {
        if ($this->taggable()) {
            return $this->pool->invalidateTags($tags);
        }

        throw new UnsupportedTaggingException("This adapter does not support tagging.");
    }

    /**
     * {@inheritDoc}
     */
    public function prune() : bool
    {
        return $this->pool instanceof PruneableInterface && $this->pool->prune() ;
    }
}
