<?php
namespace Rebet\Cache\Testable;

use Rebet\Cache\Cache;
use Rebet\Tools\Config\Config;
use Rebet\Tools\Config\Layer;

/**
 * Cache Test Helper Trait
 *
 * The assertion methods are declared static and can be invoked from any context, for instance,
 * using static::assert*() or $this->assert*() in a class that use TestHelper.
 *
 * It expect this trait to be used in below,
 *  - Class that extended PHPUnit\Framework\TestCase(actual PHPUnit\Framework\Assert) class.
 *  - Class that used Rebet\Tools\Testable\TestHelper trait.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
trait CacheTestHelper
{
    /**
     * Apply tests to all or given defined cache store.
     *
     * @param \Closure $test function(Store $store, string $store_name) { ... }
     * @param string ...$dbs that are test targets
     * @return void
     */
    public static function eachStore(\Closure $test, bool $taggable = false, string ...$stores)
    {
        Config::clear(Cache::class, Layer::RUNTIME);
        Cache::clear();
        $stores = empty($stores) ? array_keys(Cache::config('stores')) : $stores ;
        foreach ($stores as $name) {
            if ($taggable) {
                Config::runtime([Cache::class => ['stores' => [$name => ['adapter' => ['taggable' => true]]]]]);
            }
            $store = Cache::store($name);
            $store->flush();
            $test($store, $name);
            $store->flush();
        }
    }

    // ========================================================================
    // Dependent PHPUnit\Framework\Assert assertions
    // ========================================================================


    // ========================================================================
    // Dependent Rebet\Tools\Testable\TestHelper methods and assertions
    // ========================================================================


    // ========================================================================
    // Extended assertions
    // ========================================================================
}
