<?php
namespace Rebet\Cache;

use DateTimeInterface;
use Rebet\Cache\Adapter\Adapter;
use Rebet\Tools\Arrays;
use Rebet\Tools\Math\Unit;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Store Class
 *
 * https://github.com/laravel/framework/blob/7.x/src/Illuminate/Contracts/Cache/Store.php
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Store
{
    /**
     * Name of this store
     *
     * @var string
     */
    protected $name;

    /**
     * @var Adapter of cache store
     */
    protected $adapter;

    /**
     * Create cache store of given adapter.
     *
     * @param string $name
     * @param Adapter $adapter
     */
    public function __construct(string $name, Adapter $adapter)
    {
        $this->name    = $name;
        $this->adapter = $adapter;
    }

    /**
     * Get name of this store.
     *
     * @return string
     */
    public function name() : string
    {
        return $this->name;
    }

    /**
     * Get the adapter
     *
     * @return Adapter
     */
    public function adapter() : Adapter
    {
        return $this->adapter;
    }

    /**
     * Clear cache values.
     *
     * @return bool
     */
    public function flush() : bool
    {
        return $this->adapter->clear();
    }

    /**
     * Set expire to given cache item.
     *
     * @param ItemInterface $item
     * @param int|string|DateTimeInterface $expire when int given then it's lifetime seconds, when string given then it's lifetime text like '12min', when DateTime given then it's expire at given date time.
     * @return void
     */
    public static function setExpireTo(ItemInterface &$item, $expire) : void
    {
        if ($expire instanceof DateTimeInterface) {
            $item->expiresAt($expire);
        } else {
            $item->expiresAfter(Unit::of(Unit::TIME)->convert($expire, 's')->toInt());
        }
    }

    /**
     * Fetches a value from the cache or computes and remembers (if needed) it if not found.
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
            static::setExpireTo($item, $expire);
            $this->adapter->save($item);
        }
        return $value;
    }

    /**
     * Get a value/values of given key/keys from the cache.
     *
     * When a single key given, you can write `$foo = Cache::get('foo');`.
     * When multiple keys given, you can write `$values = Cache::get('foo', 'bar');` and can be access `$values['foo']`.
     * Also this method keep given keys order, so you can write `[$foo, $bar] = Cache::get('foo', 'bar');` too.
     *
     * @param string ...$keys
     * @return mixed|mixed[]
     */
    public function get(string ...$keys)
    {
        $items = [];
        foreach ($this->adapter->getItems($keys) as $item) {
            $items[$item->getKey()] = $item->get();
        }

        // Keep given keys order for `[$foo, $bar] = Cache::get('foo', 'bar');`
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = $items[$key];
        }

        return Arrays::peel($values);
    }

    /**
     * Store a item/items in the cache for a given expire.
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
            static::setExpireTo($item, $expire);
            $ok = $this->adapter->saveDeferred($item) && $ok;
        }
        return $this->adapter->commit() && $ok;
    }

    /**
     * Confirms if the cache contains specified cache item.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key) : bool
    {
        return $this->adapter->getItem($key)->isHit();
    }

    /**
     * Fetches a value/values from the store and removes it/them.
     *
     * When a single key given, you can write `$foo = Cache::pull('foo');`.
     * When multiple keys given, you can write `$values = Cache::pull('foo', 'bar');` and can be access `$values['foo']`.
     * Also this method keep given keys order, so you can write `[$foo, $bar] = Cache::pull('foo', 'bar');` too.
     *
     * @param string ...$keys
     * @return mixed|mixed[]
     */
    public function pull(string ...$keys)
    {
        $values = $this->get(...$keys);
        $this->delete(...$keys);
        return $values;
    }

    /**
     * Removes an item/items from the pool.
     *
     * @param string ...$keys
     * @return bool
     */
    public function delete(string ...$keys) : bool
    {
        return $this->adapter->deleteItems($keys);
    }

    /**
     * Begin executing a new tags operation.
     *
     * @param string ...$tags
     * @return TagSet
     * @throws UnsupportedTaggingException when the adapter does not support tagging.
     */
    public function tags(string ...$tags) : TagSet
    {
        return new TagSet($this->adapter, $tags);
    }

    /**
     * Execute pruning (deletion) of all expired cache items.
     * If the adapter not supported pruning then just do nothing and return false.
     *
     * @return bool
     */
    public function prune() : bool
    {
        return $this->adapter->prune();
    }
}
