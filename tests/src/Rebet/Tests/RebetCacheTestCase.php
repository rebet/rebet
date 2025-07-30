<?php
namespace Rebet\Tests;

use Rebet\Cache\Adapter\Symfony\ApcuAdapter;
use Rebet\Cache\Adapter\Symfony\ArrayAdapter;
use Rebet\Cache\Adapter\Symfony\FilesystemAdapter;
use Rebet\Cache\Adapter\Symfony\MemcachedAdapter;
use Rebet\Cache\Adapter\Symfony\PdoAdapter;
use Rebet\Cache\Adapter\Symfony\RedisAdapter;
use Rebet\Cache\Cache;
use Rebet\Cache\Testable\CacheTestHelper;
use Rebet\Tools\Config\Config;

/**
 * Rebet Cache Test Case Class
 *
 * We define various helper methods to reduce the labor of testing.
 */
abstract class RebetCacheTestCase extends RebetDatabaseTestCase
{
    use CacheTestHelper;

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
                    'redis' => [
                        'adapter'   => [
                            '@factory' => RedisAdapter::class,
                            'dsn'      => 'redis://redis/0',
                        ],
                    ],
                ],
                'default_store' => 'array',
            ],
        ]);
    }
}
