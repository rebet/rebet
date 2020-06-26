<?php
namespace Rebet\Tests\Http\Session\Storage;

use Rebet\Http\Session\Storage\ArraySessionStorage;
use Rebet\Http\Session\Storage\Bag\AttributeBag;
use Rebet\Tests\RebetTestCase;

class ArraySessionStorageTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(ArraySessionStorage::class, new ArraySessionStorage());
    }

    public function test_regenerate()
    {
        $storage = new ArraySessionStorage();
        $storage->start();
        $storage->registerBag(new AttributeBag('attributes'));
        $storage->getBag('attributes')->set('foo', 'bar');

        $this->assertSame('bar', $storage->getBag('attributes')->get('foo'));

        $id = $storage->getId();
        $storage->regenerate();
        $this->assertNotSame($id, $storage->getId());
        $this->assertSame('bar', $storage->getBag('attributes')->get('foo'));

        $storage->regenerate(true);
        $this->assertNotSame($id, $storage->getId());
        $this->assertSame(null, $storage->getBag('attributes')->get('foo'));
    }
}
