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
use Rebet\Tools\Utility\Files;

/**
 * Rebet Cache Test Case Class
 *
 * We define various helper methods to reduce the labor of testing.
 */
abstract class RebetCacheTestCase extends RebetDatabaseTestCase
{
    protected $test_dir;

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
        $this->test_dir = static::$unittest_cwd.'/cache';
        mkdir($this->test_dir, 0777, true);
        Config::application([
            Cache::class => [
                'stores!' => [
                    'apcu' => [
                        'adapter'   => ApcuAdapter::class,
                    ],
                    'array' => [
                        'adapter'   => ArrayAdapter::class,
                    ],
                    'file' => [
                        'adapter'   => FilesystemAdapter::class,
                        'directory' => $this->test_dir.'/file',
                    ],
                    'memcached' => [
                        'adapter'   => MemcachedAdapter::class,
                        'dsn'       => 'memcached://localhost:11211',
                    ],
                    'pdo-sqlite' => [
                        'adapter'   => PdoAdapter::class,
                        'db'        => 'sqlite',
                    ],
                    'pdo-mysql' => [
                        'adapter'   => PdoAdapter::class,
                        'db'        => 'mysql',
                    ],
                    'pdo-pgsql' => [
                        'adapter'   => PdoAdapter::class,
                        'db'        => 'pgsql',
                    ],
                    'redis' => [
                        'adapter'   => RedisAdapter::class,
                        'dsn'       => 'redis://localhost/0',
                    ],
                ],
                'default_store' => 'array',
            ],
        ]);
    }

    protected function tearDown() : void
    {
        Files::removeDir($this->test_dir);
        parent::tearDown();
    }

    protected function eachStore(\Closure $test, bool $taggable = false, string ...$stores)
    {
        Config::clear(Cache::class, Layer::RUNTIME);
        Cache::clear();
        $stores = empty($stores) ? array_keys(Cache::config('stores')) : $stores ;
        $skiped = [];
        foreach ($stores as $name) {
            $adapter = Cache::config("stores.{$name}.adapter");
            if (method_exists($adapter, 'isSupported') && !$adapter::isSupported()) {
                $skiped[] = $name;
                continue;
            }
            if ($taggable) {
                Config::runtime([Cache::class => ['stores' => [$name => ['taggable' => true]]]]);
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
