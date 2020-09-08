<?php
namespace Rebet\Tests\Cache\Adapter\Symfony;

use Rebet\Cache\Adapter\Symfony\MemcachedAdapter;
use Rebet\Tests\RebetTestCase;

/**
 *
 * # Setup Memcachd PHP Extension for Windows
 * 1. Get Memcachd dll from PECL
 *    https://github.com/lifenglsf/php_memcached_dll
 * 2. Copy `php_memcached.dll` to `{PHP_HOME}/ext`
 * 3. Copy `libmemcached.dll` to `C:/Windows/System32`
 * 4. Edit php.ini
 *    ```
 *    extension=php_memcached.dll
 *    ```
 *
 * @see https://github.com/lifenglsf/php_memcached_dll
 *
 * @requires extension memcached
 */
class MemcachedAdapterTest extends RebetTestCase
{
    protected function setUp() : void
    {
        if (!MemcachedAdapter::isSupported()) {
            $this->markTestSkipped('Memcached is not enabled.');
        }
        parent::setUp();
    }

    public function test___construct()
    {
        $dsn = 'memcached://localhost:11211';
        $this->assertInstanceOf(MemcachedAdapter::class, new MemcachedAdapter($dsn, []));
        $this->assertInstanceOf(MemcachedAdapter::class, new MemcachedAdapter($dsn, [], '', 5));
        $this->assertInstanceOf(MemcachedAdapter::class, new MemcachedAdapter($dsn, [], '', '5min'));
    }
}
