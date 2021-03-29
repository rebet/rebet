<?php
namespace Rebet\Tests;

use Rebet\Cache\Adapter\Symfony\ApcuAdapter;
use Rebet\Cache\Adapter\Symfony\ArrayAdapter;
use Rebet\Cache\Adapter\Symfony\FilesystemAdapter;
use Rebet\Cache\Adapter\Symfony\MemcachedAdapter;
use Rebet\Cache\Adapter\Symfony\PdoAdapter;
use Rebet\Cache\Adapter\Symfony\RedisAdapter;
use Rebet\Cache\Cache;
use Rebet\Tools\Config\Config;
use Rebet\Tools\Config\Layer;

/**
 * Rebet Cache Test Case Class
 *
 * We define various helper methods to reduce the labor of testing.
 */
abstract class RebetCacheTestCase extends RebetDatabaseTestCase
{
    public static function setUpBeforeClass() : void
    {
        parent::setUpBeforeClass();
    }

    public static function tearDownAfterClass() : void
    {
        parent::tearDownAfterClass();
    }

    protected function setUp() : void
    {
        parent::setUp();
        $test_dir = $this->makeSubWorkingDir('/cache');
        Config::application([
            Cache::class => [
                'stores=' => [
                    'apcu' => [
                        'adapter' => [
                            '@factory' => ApcuAdapter::class,
                        ],
                    ],
                    'array' => [
                        'adapter' => [
                            '@factory' => ArrayAdapter::class,
                        ],
                    ],
                    'file' => [
                        'adapter' => [
                            '@factory'  => FilesystemAdapter::class,
                            'directory' => $test_dir.'/file',
                        ],
                    ],
                    'memcached' => [
                        'adapter' => [
                            '@factory' => MemcachedAdapter::class,
                            'dsn'      => 'memcached://memcached:11211',
                        ],
                    ],
                    'pdo-sqlite' => [
                        'adapter' => [
                            '@factory' => PdoAdapter::class,
                            'db'       => 'sqlite',
                        ],
                    ],
                    'pdo-mysql' => [
                        'adapter'   => [
                            '@factory' => PdoAdapter::class,
                            'db'       => 'mysql',
                        ],
                    ],
                    'pdo-mariadb' => [
                        'adapter'   => [
                            '@factory' => PdoAdapter::class,
                            'db'       => 'mariadb',
                        ],
                    ],
                    'pdo-pgsql' => [
                        'adapter'   => [
                            '@factory' => PdoAdapter::class,
                            'db'       => 'pgsql',
                        ],
                    ],
                    // ----------------------------------
                    // @todo Symfony Cache ver 5.2.1 has problem on createTable() when use sqlsrv PDO, so this code comment out currently.
                    // @see https://github.com/symfony/symfony/issues/39793
                    // ----------------------------------
                    // 'pdo-sqlsrv' => [
                    //     'adapter'   => [
                    //         '@factory' => PdoAdapter::class,
                    //         'db'       => 'sqlsrv',
                    //     ],
                    // ],
                    'redis' => [
                        'adapter'   => [
                            '@factory' => RedisAdapter::class,
                            'dsn'      => 'redis://redis/0',
                            // 'dsn'      => 'redis://localhost/0',
                        ],
                    ],
                ],
                'default_store' => 'array',
            ],
        ]);
    }

    protected function eachStore(\Closure $test, bool $taggable = false, string ...$stores)
    {
        Config::clear(Cache::class, Layer::RUNTIME);
        Cache::clear();
        $stores = empty($stores) ? array_keys(Cache::config('stores')) : $stores ;
        $skiped = [];
        foreach ($stores as $name) {
            $adapter = Cache::config("stores.{$name}.adapter.@factory");
            if (method_exists($adapter, 'isSupported') && !$adapter::isSupported()) {
                $skiped[] = $name;
                continue;
            }
            if ($taggable) {
                Config::runtime([Cache::class => ['stores' => [$name => ['adapter' => ['taggable' => true]]]]]);
            }
            $store = Cache::store($name);
            if ($store === null) {
                $skiped[] = $name;
                continue;
            }
            $store->flush();
            $test($store, "on {$name}");
            $store->flush();
        }
        if (!empty($skiped)) {
            $this->markTestSkipped("Cache store ".implode(", ", $skiped)." was not ready.");
        }
    }
}
