<?php

use Rebet\Application\App;
use Rebet\Cache\Adapter\Symfony\ApcuAdapter;
use Rebet\Cache\Adapter\Symfony\ArrayAdapter;
use Rebet\Cache\Adapter\Symfony\FilesystemAdapter;
use Rebet\Cache\Adapter\Symfony\MemcachedAdapter;
use Rebet\Cache\Adapter\Symfony\PdoAdapter;
use Rebet\Cache\Adapter\Symfony\RedisAdapter;
use Rebet\Cache\Cache;
use Rebet\Tools\Utility\Env;

/*
|##################################################################################################
| Cache Package Configurations
|##################################################################################################
| This file defines configuration for classes in Rebet\Cache package.
|
| The Rebet configuration file provides multiple ways to describe environment-dependent settings.
| You can use these methods when set environment-dependent settings.
|
| 1. Use `Env::promise('KEY')` to get value from `.env` file for each environment.
| 2. Use `App::when(['env' => value, ...])` to switch value by channel and environment.
| 3. Use `cache@{env}.php` file to override environment dependency value of `cache.php`
|
| You can also use `Config::refer()` to refer to the settings of other classes, use
| `Config::promise()` to get the settings by lazy evaluation, and have the values evaluated each
| time the settings are referenced.
|
| NOTE: If you want to get other default setting samples of configuration file, try check here.
|       https://github.com/rebet/rebet/tree/master/src/Rebet/Application/Console/Command/skeltons/configs
*/
return [
    /*
    |==============================================================================================
    | Cache Configuration
    |==============================================================================================
    | This section defines cache settings.
    | You may change these defaults as required.
    */
    Cache::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Default Cache Store
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | This option controls the default cache connection that gets used while using this caching
        | library. This connection is used when another is not explicitly specified when executing
        | a given caching function.
        */
        //{%-- commentif !$use_cache, '// ', '--- Please uncomment if you want to use cache ---' -%}
        'default_store' => '{! $cache !}',
        //{%-- endcommentif -%}


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Cache Stores
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Here you may define all of the cache "stores" for your application as well as their
        | drivers. You may even define multiple stores for the same cache driver to group types of
        | items stored in your caches.
        |
        | Supported:
        |  - @see Rebet\Cache\Adapter\Symfony\ApcuAdapter
        |  - @see Rebet\Cache\Adapter\Symfony\ArrayAdapter
        |  - @see Rebet\Cache\Adapter\Symfony\FilesystemAdapter
        |  - @see Rebet\Cache\Adapter\Symfony\MemcachedAdapter
        |  - @see Rebet\Cache\Adapter\Symfony\PdoAdapter
        |  - @see Rebet\Cache\Adapter\Symfony\RedisAdapter
        |  - and also you can use any cache adapter that implemented Rebet\Cache\Adapter\Adapter.
        */
        'stores' => [
            //{%-- if $cache == 'apcu' -%}
            /*
            |--------------------------------------------------------------------------------------
            | APCu Cache Store
            |--------------------------------------------------------------------------------------
            | A cache store based on APCu.
            | If you want to use this adapter then you have to install and enabled APCu extension.
            */
            //{%-- commentif !$use_cache, '// ', '--- Please uncomment if you want to use cache ---' -%}
            'apcu' => [
                'adapter' => [
                    '@factory' => ApcuAdapter::class,
                    // --- You can change only what you need for these default options ---
                    // 'namespace'              => '',
                    // 'default_lifetime'       => 0,     // You can set time unit labeled string like '12min', or int seconds.
                    // 'version'                => null,
                    // 'taggable'               => false,
                    // 'tags_pool'              => null,  // [when taggable] You can set name that `Cache.stores.{name}` or CacheItemPoolInterface instance.
                    // 'known_tag_versions_ttl' => 0.15,  // [when taggable]
                ],
            ],
            //{%-- endcommentif -%}
            //{%-- endif -%}
            //{%-- if $cache == 'file' -%}
            /*
            |--------------------------------------------------------------------------------------
            | File Cache Store
            |--------------------------------------------------------------------------------------
            | A cache store based on a file system.
            */
            //{%-- commentif !$use_cache, '// ', '--- Please uncomment if you want to use cache ---' -%}
            'file' => [
                'adapter' => [
                    '@factory' => FilesystemAdapter::class,
                    // --- You can change only what you need for these default options ---
                    // 'namespace'              => '',
                    // 'default_lifetime'       => 0,     // You can set time unit labeled string like '12min', or int seconds.
                    // 'directory'              => null,
                    // 'marshaller'             => null,  // Instance of Symfony\Component\Cache\Marshaller\MarshallerInterface
                    // 'taggable'               => false,
                    // 'tags_pool'              => null,  // [when taggable] You can set name that `Cache.stores.{name}` or CacheItemPoolInterface instance.
                    // 'known_tag_versions_ttl' => 0.15,  // [when taggable]
                ],
            ],
            //{%-- endcommentif -%}
            //{%-- endif -%}
            //{%-- if $cache == 'memcached' -%}
            /*
            |--------------------------------------------------------------------------------------
            | Memcached Cache Store
            |--------------------------------------------------------------------------------------
            | A cache store based on a Memcached.
            | If you want to use this adapter then you have to install and enabled Memcached
            | extension.
            */
            //{%-- commentif !$use_cache, '// ', '--- Please uncomment if you want to use cache ---' -%}
            'memcached' => [
                'adapter' => [
                    '@factory' => MemcachedAdapter::class,
                    'dsn'      => App::when([
                        'local'      => 'memcached://memcached:11211',
                        'production' => ['memcached://localhost:11211', /* Secondary DSN if exists */],
                    ]),
                    'options'  => [
                        'username' => Env::promise('MEMCACHED_USERNAME'),
                        'password' => Env::promise('MEMCACHED_PASSWORD'),
                        // --- You can set any other options supported by Symfony\Component\Cache\Adapter\MemcachedAdapter::createConnection() ---
                        // 'persistent_id' => null,
                        // 'weight'        => 100,
                        // ... etc
                    ],
                    // --- You can change only what you need for these default options ---
                    // 'namespace'              => '',
                    // 'default_lifetime'       => 0,     // You can set time unit labeled string like '12min', or int seconds.
                    // 'marshaller'             => null,  // Instance of Symfony\Component\Cache\Marshaller\MarshallerInterface
                    // 'taggable'               => false,
                    // 'tags_pool'              => null,  // [when taggable] You can set name that `Cache.stores.{name}` or CacheItemPoolInterface instance.
                    // 'known_tag_versions_ttl' => 0.15,  // [when taggable]
                ],
            ],
            //{%-- endcommentif -%}
            //{%-- endif -%}
            //{%-- if $cache == 'database' -%}
            /*
            |--------------------------------------------------------------------------------------
            | Database (PDO) Cache Store
            |--------------------------------------------------------------------------------------
            | A cache store based on a database.
            */
            //{%-- commentif !$use_cache, '// ', '--- Please uncomment if you want to use cache ---' -%}
            'database' => [
                'adapter' => [
                    '@factory' => PdoAdapter::class,
                    // --- You can change only what you need for these default options ---
                    // 'db'                     => null,  // Database name of Dao.dbs.* configuration or a \PDO instance. (default: null for use default database of `Dao.dbs` configure)
                    // 'options'                => [
                    //     'db_table'        => 'cache_items',
                    //     'db_id_col'       => 'cache_item_id',
                    //     'db_data_col'     => 'data',
                    //     'db_lifetime_col' => 'lifetime',
                    //     'db_time_col'     => 'time',
                    // ],
                    // 'namespace'              => '',
                    // 'default_lifetime'       => 0,     // You can set time unit labeled string like '12min', or int seconds.
                    // 'marshaller'             => null,  // Instance of Symfony\Component\Cache\Marshaller\MarshallerInterface
                    // 'taggable'               => false,
                    // 'tags_pool'              => null,  // [when taggable] You can set name that `Cache.stores.{name}` or CacheItemPoolInterface instance.
                    // 'known_tag_versions_ttl' => 0.15,  // [when taggable]
                ],
            ],
            //{%-- endcommentif -%}
            //{%-- endif -%}
            //{%-- if $cache == 'redis' -%}
            /*
            |--------------------------------------------------------------------------------------
            | Redis Cache Store
            |--------------------------------------------------------------------------------------
            | A cache store based on Redis.
            | If you want to use this adapter then you have to install and enabled Redis extension
            | or require 'predis/predis' modules.
            |
            | 'dsn' option can be an array of dsns, a DSN, or an array of DSNs formatted below
            |  - redis://[pass@][ip|host|socket[:port]][/db-index]
            |  - redis:?host[redis1:26379]&host[redis2:26379]&host[redis3:26379]&redis_sentinel=mymaster
            */
            //{%-- commentif !$use_cache, '// ', '--- Please uncomment if you want to use cache ---' -%}
            'redis' => [
                'adapter'   => [
                    '@factory' => RedisAdapter::class,
                    'dsn'      => App::when([
                        'local'      => 'redis://redis/0',
                        'production' => 'redis://localhost/0',
                    ]),
                    // --- You can change only what you need for these default options ---
                    // 'options'  => [
                    //     // --- You can set any other options supported by Rebet\Cache\Adapter\Symfony\RedisAdapter::__construct() ---
                    //     // 'timeout' => 0,
                    //     // ... etc
                    // ],
                    // 'namespace'              => '',
                    // 'default_lifetime'       => 0,     // You can set time unit labeled string like '12min', or int seconds.
                    // 'marshaller'             => null,  // Instance of Symfony\Component\Cache\Marshaller\MarshallerInterface
                    // 'taggable'               => false,
                    // 'tags_pool'              => null,  // [when taggable] You can set name that `Cache.stores.{name}` or CacheItemPoolInterface instance.
                    // 'known_tag_versions_ttl' => 0.15,  // [when taggable]
                ],
            ],
            //{%-- endcommentif -%}
            //{%-- endif -%}


            /*
            |--------------------------------------------------------------------------------------
            | Array Cache Store
            |--------------------------------------------------------------------------------------
            | A cache store based on array in memory.
            | This adapter is primarily intended for testing use.
            */
            //{%-- commentif !$use_cache, '// ', '--- Please uncomment if you want to use cache ---' -%}
            'array' => [
                'adapter' => [
                    '@factory' => ArrayAdapter::class,
                    // --- You can change only what you need for these default options ---
                    // 'namespace'              => '',
                    // 'default_lifetime'       => 0,     // You can set time unit labeled string like '12min', or int seconds.
                    // 'taggable'               => false,
                    // 'tags_pool'              => null,  // [when taggable] You can set name that `Cache.stores.{name}` or CacheItemPoolInterface instance.
                    // 'known_tag_versions_ttl' => 0.15,  // [when taggable]
                ],
            ],
            //{%-- endcommentif -%}
        ],
    ],
];
