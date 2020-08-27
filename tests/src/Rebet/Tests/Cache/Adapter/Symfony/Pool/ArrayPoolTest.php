<?php
namespace Rebet\Tests\Cache\Adapter\Symfony\Pool;

use Rebet\Cache\Adapter\Symfony\Pool\ArrayPool;
use Rebet\Tests\RebetTestCase;
use Symfony\Component\Cache\Adapter\ProxyAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\CacheItem;

class ArrayPoolTest extends RebetTestCase
{
    /**
     * @return AdapterInterface[]
     */
    protected $pools = [];

    public function setUp()
    {
        parent::setUp();
        $this->pools[] = new ArrayPool();
        $this->pools[] = new ProxyAdapter(new ArrayPool());
        $this->pools[] = new TagAwareAdapter(new ArrayPool());
        $this->pools[] = new TagAwareAdapter(new ProxyAdapter(new ArrayPool()));
        foreach ($this->pools as $pool) {
            $pool->clear();
        }
    }

    public function test___construct()
    {
        $this->assertInstanceOf(ArrayPool::class, new ArrayPool());
    }

    public function test_getItemAndSave()
    {
        foreach ($this->pools as $pool) {
            $item = $pool->getItem('foo');
            $this->assertInstanceOf(CacheItem::class, $item);
            $this->assertSame('foo', $item->getKey());
            $this->assertSame(null, $item->get());
            $this->assertSame(false, $item->isHit());

            $item->set(1);
            $item->expiresAfter(1);
            $pool->save($item);

            $item = $pool->getItem('foo');
            $this->assertInstanceOf(CacheItem::class, $item);
            $this->assertSame('foo', $item->getKey());
            $this->assertSame(1, $item->get());
            $this->assertSame(true, $item->isHit());

            for ($i = 0 ; $pool->hasItem('foo') && $i < 30 ; $i++) {
                usleep(100000);
            }

            $item = $pool->getItem('foo');
            $this->assertInstanceOf(CacheItem::class, $item);
            $this->assertSame('foo', $item->getKey());
            $this->assertSame(null, $item->get());
            $this->assertSame(false, $item->isHit());

            $item->set(1);
            $pool->save($item);

            $item = $pool->getItem('foo');
            $this->assertInstanceOf(CacheItem::class, $item);
            $this->assertSame('foo', $item->getKey());
            $this->assertSame(1, $item->get());
            $this->assertSame(true, $item->isHit());

            $item->expiresAfter(-1);
            $pool->save($item);

            $item = $pool->getItem('foo');
            $this->assertInstanceOf(CacheItem::class, $item);
            $this->assertSame('foo', $item->getKey());
            $this->assertSame(null, $item->get());
            $this->assertSame(false, $item->isHit());
        }
    }

    public function test_getItems()
    {
        foreach ($this->pools as $pool) {
            $values = ['foo' => null , 'bar' => null];
            $hits   = ['foo' => false, 'bar' => false];
            $keys   = array_keys($values);
            $items  = $pool->getItems($keys);
            // $this->assertSame(2, count($items));
            foreach ($items as $key => $item) {
                $this->assertInstanceOf(CacheItem::class, $item);
                $this->assertSame($key, $item->getKey());
                $this->assertArrayHasKey($key, $values);
                $this->assertSame($values[$key], $item->get(), 'key='.$item->getKey());
                $this->assertSame($hits[$key], $item->isHit(), 'key='.$item->getKey());
            }

            $item = $pool->getItem('foo');
            $item->set(1);
            $item->expiresAfter(1);
            $pool->save($item);

            $values = ['foo' => 1   , 'bar' => null];
            $hits   = ['foo' => true, 'bar' => false];
            $items  = $pool->getItems($keys);
            // $this->assertSame(2, count($items));
            foreach ($items as $key => $item) {
                $this->assertInstanceOf(CacheItem::class, $item);
                $this->assertSame($key, $item->getKey());
                $this->assertArrayHasKey($key, $values);
                $this->assertSame($values[$key], $item->get(), 'key='.$item->getKey());
                $this->assertSame($hits[$key], $item->isHit(), 'key='.$item->getKey());
            }
        }
    }

    public function test_hasItem()
    {
        foreach ($this->pools as $pool) {
            $this->assertSame(false, $pool->hasItem('foo'));

            $item = $pool->getItem('foo');
            $item->set(1);
            $item->expiresAfter(1);
            $pool->save($item);

            $this->assertSame(true, $pool->hasItem('foo'));

            for ($i = 0 ; $pool->hasItem('foo') && $i < 30 ; $i++) {
                usleep(100000);
            }

            $this->assertSame(false, $pool->hasItem('foo'));


            $this->assertSame(false, $pool->hasItem('bar'));
            $item = $pool->getItem('bar');
            $item->set(null);
            $pool->save($item);
            $this->assertSame(true, $pool->hasItem('bar'));
        }
    }

    public function test_clear()
    {
        foreach ($this->pools as $pool) {
            $this->assertSame(false, $pool->hasItem('foo'));

            $item = $pool->getItem('foo');
            $item->set(1);
            $pool->save($item);

            $this->assertSame(true, $pool->hasItem('foo'));
            $this->assertSame(true, $pool->clear());
            $this->assertSame(false, $pool->hasItem('foo'));

            foreach (['foo_1', 'foo_2', 'bar_1'] as $key) {
                $item = $pool->getItem($key);
                $item->set(1);
                $pool->save($item);
            }

            $this->assertSame(true, $pool->hasItem('foo_1'));
            $this->assertSame(true, $pool->hasItem('foo_2'));
            $this->assertSame(true, $pool->hasItem('bar_1'));
            $this->assertSame(true, $pool->clear('foo_'));
            $this->assertSame(false, $pool->hasItem('foo_1'));
            $this->assertSame(false, $pool->hasItem('foo_2'));
            $this->assertSame(true, $pool->hasItem('bar_1'));
        }
    }

    public function test_deleteItem()
    {
        foreach ($this->pools as $pool) {
            $item = $pool->getItem('foo');
            $item->set(1);
            $pool->save($item);
            $this->assertSame(true, $pool->hasItem('foo'));
            $this->assertSame(true, $pool->deleteItem('foo'));
            $this->assertSame(false, $pool->hasItem('foo'));
        }
    }

    public function test_saveDeferredAndCommit()
    {
        foreach ($this->pools as $pool) {
            $this->assertSame(false, $pool->hasItem('foo'));

            $item = $pool->getItem('foo');
            $item->set(1);
            $pool->saveDeferred($item);

            if ($pool instanceof TagAwareAdapterInterface) {
                $this->assertSame(true, $pool->hasItem('foo'));
            } else {
                $this->assertSame(false, $pool->hasItem('foo'));
            }
            $this->assertSame(true, $pool->commit());
            $this->assertSame(true, $pool->hasItem('foo'));
        }
    }
}
