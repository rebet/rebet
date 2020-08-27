<?php
namespace Rebet\Cache;

use DateTimeInterface;
use Rebet\Cache\Adapter\Adapter;
use Rebet\Cache\Exception\CacheException;

/**
 * Tag Set class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class TagSet
{
    /**
     * @var Adapter of cache store
     */
    protected $adapter;

    /**
     * @var array of tags
     */
    protected $tags;

    /**
     * Create cache store with tags using given adapter.
     *
     * @param Adapter $adapter
     * @param string[] $tags
     * @throws UnsupportedTaggingException when the adapter does not support tagging.
     */
    public function __construct(Adapter $adapter, array $tags)
    {
        if (!$adapter->taggable()) {
            throw new CacheException("The adapter does not support tagging.");
        }
        $this->adapter = $adapter;
        $this->tags    = $tags;
    }

    /**
     * Fetches a value from the store or computes and remembers (if needed) it with tags if not found.
     * On cache misses, a supplier is called that should return the missing value.
     *
     * @param string $key of the item to retrieve from the cache
     * @param int|string|DateTimeInterface $expire when int given then it's lifetime seconds, when string given then it's lifetime text like '12min', when DateTime given then it's expire at given date time.
     * @param \Closure|mixed $supplier that Closure of `function():mixed { ... }`, otherwise mixed value return given value as it is.
     * @param bool $remember (default: true)
     * @return mixed
     */
    public function retrieve(string $key, $expire, $supplier, bool $remember = true)
    {
        $item = $this->adapter->getItem($key);
        if ($item->isHit()) {
            return $item->get();
        }

        $value = $supplier instanceof \Closure ? $supplier() : $supplier ;
        if ($remember) {
            $item->set($value);
            Store::setExpireTo($item, $expire);
            $this->adapter->saveWithTags($item, $this->tags);
        }
        return $value;
    }

    /**
     * Store a item/items with tags in the cache for a given expire.
     *
     * @param array $values of ['key' => 'value', ...]
     * @param int|string|DateTimeInterface $expire when int given then it's lifetime seconds, when string given then it's lifetime text like '12min', when DateTime given then it's expire at given date time.
     * @return bool
     */
    public function put(array $values, $expire) : bool
    {
        $ok = true;
        foreach ($this->adapter->getItems(array_keys($values)) as $item) {
            $item->set($values[$item->getKey()] ?? null);
            Store::setExpireTo($item, $expire);
            $ok = $this->adapter->saveDeferredWithTags($item, $this->tags) && $ok;
        }
        return $this->adapter->commit() && $ok;
    }

    /**
     * Invalidates cached items using tags.
     *
     * When implemented on a PSR-6 pool, invalidation should not apply
     * to deferred items. Instead, they should be committed as usual.
     * This allows replacing old tagged values by new ones without
     * race conditions.
     *
     * @return boolean
     */
    public function flush() : bool
    {
        return $this->adapter->clearByTags($this->tags);
    }
}
