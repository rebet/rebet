<?php
namespace Rebet\Tests\Cache;

use Rebet\Cache\Adapter\Symfony\ArrayAdapter;
use Rebet\Cache\Adapter\Symfony\FilesystemAdapter;
use Rebet\Cache\Cache;
use Rebet\Cache\Store;
use Rebet\Tests\RebetCacheTestCase;

class CacheTest extends RebetCacheTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(Store::class, new Store('test', new ArrayAdapter()));
    }

    public function test_clear()
    {
        $this->assertEmpty($this->inspect(Cache::class, 'stores'));
        $store = Cache::store();
        $this->assertNotEmpty($this->inspect(Cache::class, 'stores'));
        Cache::clear();
        $this->assertEmpty($this->inspect(Cache::class, 'stores'));
    }

    public function test_store()
    {
        $this->assertSame('array', Cache::store()->name());
        $this->assertInstanceOf(FilesystemAdapter::class, Cache::store('file')->adapter());
    }

    public function test___callStatic()
    {
        $this->assertSame('array', Cache::name());
    }
}
