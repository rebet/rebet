<?php
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
     * @var array of [key => [value, expiry], ...]
     */
    protected $pool = [];

    /**
     * @var CacheItem[]
     */
    protected $deferred = [];

    /**
     * @var \Closure of function($key, $value, $isHit) : CacheItem {};
     */
    protected $item_creator;

    /**
     * Undocumented function
     */
    public function __construct()
    {
        $this->item_creator = \Closure::bind(
            static function ($key, $value, $isHit) {
                $item = new CacheItem();
                $item->key = $key;
                $item->value = $value;
                $item->isHit = $isHit;
                return $item;
            },
            null,
            CacheItem::class
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getItem($key)
    {
        $value = ($isHit = $this->hasItem($key)) ? $this->pool[$key][0] : null ;
        return call_user_func($this->item_creator, $key, $value, $isHit);
    }

    /**
     * {@inheritDoc}
     */
    public function getItems(array $keys = [])
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
    public function hasItem($key)
    {
        if (\is_string($key) && isset($this->pool[$key]) && $this->pool[$key][1] > microtime(true)) {
            return true;
        }
        $this->deleteItem($key);
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function clear(/*string $prefix = ''*/)
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
    public function deleteItem($key)
    {
        CacheItem::validateKey($key);
        unset($this->pool[$key]);
        return true;
    }

    /**
     * {@inheritDoc}
    */
    public function deleteItems(array $keys)
    {
        foreach ($keys as $key) {
            $this->deleteItem($key);
        }
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function save(CacheItemInterface $item)
    {
        if (!$item instanceof CacheItem) {
            return false;
        }
        $item   = (array) $item;
        $key    = $item["\0*\0key"];
        $value  = $item["\0*\0value"];
        $expiry = $item["\0*\0expiry"];

        if (!empty($expiry) && $expiry <= microtime(true)) {
            $this->deleteItem($key);
            return true;
        }

        $this->pool[$key] = [$value, !empty($expiry) ? $expiry : PHP_INT_MAX];
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function saveDeferred(CacheItemInterface $item)
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
    public function commit()
    {
        foreach ($this->deferred as $item) {
            $this->save($item);
        }
        $this->deferred = [];
        return true;
    }
}
