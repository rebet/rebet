<?php
namespace Rebet\Cache;

use Rebet\Tools\Config\Configurable;

/**
 * Cache Class
 *
 * Cache Access Object by specifying any adapter for each store definition.
 * The adapter MUST be implements Adapter Interface.
 *
 * The adapter used for Rebet cache access object can be specified by the following definition.
 *
 *     Cache::class => [
 *         'stores' => [
 *              'name' => [                         // Alias ​​for classification, Not a store name
 *                  'adapter'    => Adapter::class, // Adapter Interface implementation class
 *                  'arg_name_1' => value_1,        // Constructor argument name and value for 'adapter' class.
 *                  (snip)                          // If the argument has default value (or variadic), then the parameter can be optional.
 *                  'arg_name_n' => value_n,        // Also, you don't have to worry about the order of parameter definition.
 *              ],
 *         ]
 *     ]
 *
 * If it is difficult to build a adapter with simple constructor parameter specification, you can build a adapter by specifying a factory method.
 *
 *     Cache::class => [
 *         'stores' => [
 *              'name' => [
 *                  'adapter' => function() { ... Build any cache adapter here ... } , // Return Adapter Interface implementation class
 *              ],
 *         ]
 *     ]
 *
 * Based on this specification, Rebet provides Adapters class.
 * The drivers prepared in the package are as follows.
 *
 * Adapters
 * --------------------
 * @see \Rebet\Cache\Adapter\Symfony\ApcuAdapter::class
 * @see \Rebet\Cache\Adapter\Symfony\ArrayAdapter::class
 * @see \Rebet\Cache\Adapter\Symfony\FilesystemAdapter::class (Library Default)
 * @see \Rebet\Cache\Adapter\Symfony\MemcachedAdapter::class
 * @see \Rebet\Cache\Adapter\Symfony\PdoAdapter::class
 * @see \Rebet\Cache\Adapter\Symfony\RedisAdapter::class
 *
 * Dynamically call the default store method
 * --------------------
 * @method static string        name()                                                                                                        Dynamically call the default store method.
 * @method static Adapter       adapter()                                                                                                     Dynamically call the default store method.
 * @method static bool          flush()                                                                                                       Dynamically call the default store method.
 * @method static mixed         retrieve(string $key, int|string|DateTimeInterface $expire, \Closure|mixed $supplier, bool $remember = true)  Dynamically call the default store method.
 * @method static mixed|mixed[] get(string ...$keys)                                                                                          Dynamically call the default store method.
 * @method static bool          put(array $values, int|string|DateTimeInterface $expire)                                                      Dynamically call the default store method.
 * @method static bool          has(string $key)                                                                                              Dynamically call the default store method.
 * @method static mixed|mixed[] pull(string ...$keys)                                                                                         Dynamically call the default store method.
 * @method static bool          delete(string ...$keys)                                                                                       Dynamically call the default store method.
 * @method static TagSet        tags(string ...$tags)                                                                                         Dynamically call the default store method.
 * @method static bool          prune()                                                                                                       Dynamically call the default store method.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Cache
{
    use Configurable;

    /**
     * {@inheritDoc}
     * @see https://github.com/rebet/rebet/blob/master/src/Rebet/Application/Console/Command/skeltons/configs/cache.letterpress.php
     */
    public static function defaultConfig()
    {
        return [
            'default_store' => null,
            'stores'        => [],
        ];
    }

    /**
     * Cache stores
     *
     * @var Store[]
     */
    protected static $stores = [];

    /**
     * No instantiation
     */
    private function __construct()
    {
    }

    /**
     * Crear the All Cache store instance.
     *
     * @return void
     */
    public static function clear() : void
    {
        static::$stores = [];
    }

    /**
     * Get the Cache store for given name.
     *
     * @param string $name when the null given return the default cache store (default: null)
     * @return Store
     */
    public static function store(?string $name = null) : Store
    {
        $name = $name ?? static::config('default_store');
        return static::$stores[$name]
            ?? static::$stores[$name] = new Store($name, static::configInstantiate("stores.{$name}.adapter"))
            ;
    }

    /**
     * Dynamically call the default Store instance.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return static::store()->$method(...$parameters);
    }
}
