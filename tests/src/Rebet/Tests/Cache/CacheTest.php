<?php

declare(strict_types=1);

namespace Rebet\Tests\Cache;

use Rebet\Cache\Adapter\Symfony\ArrayAdapter;
use Rebet\Cache\Adapter\Symfony\FilesystemAdapter;
use Rebet\Cache\Cache;
use Rebet\Cache\Store;
use Rebet\Tests\RebetCacheTestCase;

class CacheTest extends RebetCacheTestCase
{
    public function test___construct(): void
    {
        $this->assertInstanceOf(Store::class, new Store('test', new ArrayAdapter()));
    }

    public function test_clear(): void
    {
        $this->assertEmpty($this->inspect(Cache::class, 'stores'));
        $store = Cache::store();
        $this->assertNotEmpty($this->inspect(Cache::class, 'stores'));
        Cache::clear();
        $this->assertEmpty($this->inspect(Cache::class, 'stores'));
    }

    public function test_store(): void
    {
        $this->assertSame('array', Cache::store()->name());
        $this->assertInstanceOf(FilesystemAdapter::class, Cache::store('file')->adapter());
    }

    public function test___callStatic(): void
    {
        $this->assertSame('array', Cache::name());
    }
}
