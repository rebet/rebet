<?php
namespace Rebet\Tests\Cache\Adapter\Symfony;

use Rebet\Cache\Adapter\Symfony\MemcachedAdapter;
use Rebet\Tests\RebetTestCase;

/**
 * @see https://github.com/lifenglsf/php_memcached_dll
 *
 * @requires extension memcached
 */
class MemcachedAdapterTest extends RebetTestCase
{
    public function setUp()
    {
        if (!MemcachedAdapter::isSupported()) {
            $this->markTestSkipped('Memcached is not enabled.');
        }
        parent::setUp();
    }

    public function test___construct()
    {
        $dsn = 'memcached://localhost:11222';
        $this->assertInstanceOf(MemcachedAdapter::class, new MemcachedAdapter($dsn, []));
        $this->assertInstanceOf(MemcachedAdapter::class, new MemcachedAdapter($dsn, [], '', 5));
        $this->assertInstanceOf(MemcachedAdapter::class, new MemcachedAdapter($dsn, [], '', '5min'));
    }
}
