<?php
declare(strict_types=1);

namespace Rebet\Cache\Adapter\Symfony\Pool;

use Psr\Cache\CacheItemInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\CacheItem;

/**
 * Array Pool Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class ArrayPool implements AdapterInterface
{
    /**
     * @var array<string, array{0: mixed, 1: int|float, 2: array<string, string>|null}> of [key => [value, expiry, tags], ...]
     */
    protected $pool = [];

    /**
     * @var CacheItem[]
     */
    protected $deferred = [];

    /**
     * @var \Closure of function($key, $value, $isHit, $tags) : CacheItem {};
     */
    protected $item_creator;

    /**
     * Undocumented function
     */
    public function __construct()
    {
        $this->item_creator = \Closure::bind(
            static function ($key, $value, $isHit, $tags) {
                $item        = new CacheItem();
                $item->key   = $key;
                $item->value = $value;
                $item->isHit = $isHit;
                if (null !== $tags) {
                    $item->metadata[CacheItem::METADATA_TAGS] = $tags;
                }
                return $item;
            },
            null,
            CacheItem::class
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getItem($key) : CacheItem
    {
        $isHit = $this->hasItem($key);
        $value = $isHit ? $this->pool[$key][0] : null ;
        $tags  = $isHit ? ($this->pool[$key][2] ?? null) : null ;
        return call_user_func($this->item_creator, $key, $value, $isHit, $tags);
    }

    /**
     * {@inheritDoc}
     */
    public function getItems(array $keys = []) : iterable
    {
        $items = [];
        foreach ($keys as $key) {
            $items[$key] = $this->getItem($key);
        }
        return $items;
    }

    /**
     * {@inheritDoc}
     */
    public function hasItem(string $key) : bool
    {
        if (isset($this->pool[$key]) && $this->pool[$key][1] > microtime(true)) {
            return true;
        }
        $this->deleteItem($key);
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function clear(string $prefix = '') : bool
    {
        $prefix = 0 < \func_num_args() ? (string) func_get_arg(0) : '';
        if ('' !== $prefix) {
            foreach ($this->pool as $key => $item) {
                if (0 === strpos($key, $prefix)) {
                    unset($this->pool[$key]);
                }
            }
        } else {
            $this->pool = [];
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteItem($key) : bool
    {
        CacheItem::validateKey($key);
        unset($this->pool[$key]);
        return true;
    }

    /**
     * {@inheritDoc}
    */
    public function deleteItems(array $keys) : bool
    {
        foreach ($keys as $key) {
            $this->deleteItem($key);
        }
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function save(CacheItemInterface $item) : bool
    {
        if (!$item instanceof CacheItem) {
            return false;
        }
        $item   = (array) $item;
        $key    = $item["\0*\0key"];
        $value  = $item["\0*\0value"];
        $expiry = $item["\0*\0expiry"];
        $tags   = $item["\0*\0newMetadata"][CacheItem::METADATA_TAGS] ?? null;

        if (!empty($expiry) && $expiry <= microtime(true)) {
            $this->deleteItem($key);
            return true;
        }

        $this->pool[$key] = [$value, !empty($expiry) ? $expiry : PHP_INT_MAX, $tags];
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function saveDeferred(CacheItemInterface $item) : bool
    {
        if (!$item instanceof CacheItem) {
            return false;
        }
        $this->deferred[$item->getKey()] = $item;
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function commit() : bool
    {
        foreach ($this->deferred as $item) {
            $this->save($item);
        }
        $this->deferred = [];
        return true;
    }
}
