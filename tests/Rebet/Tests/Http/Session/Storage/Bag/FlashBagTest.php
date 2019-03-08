<?php
namespace Rebet\Tests\Http\Session\Storage\Bag;

use Rebet\Http\Session\Storage\Bag\FlashBag;
use Rebet\Tests\RebetTestCase;

class FlashBagTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(FlashBag::class, new FlashBag('test'));
    }

    public function test_getName()
    {
        $bag = new FlashBag('foo');
        $this->assertSame('foo', $bag->getName());
    }

    public function test_initialize()
    {
        $src = [];
        $bag = new FlashBag('foo');
        $bag->initialize($src);
        $this->assertSame(null, $bag->peek('name'));
        $this->assertSame(null, $src['name'] ?? null);
        $bag->set('name', 'value');
        $this->assertSame('value', $bag->peek('name'));
        $this->assertSame('value', $src['name'] ?? null);
    }

    public function test_getStorageKey()
    {
        $bag = new FlashBag('foo');
        $this->assertSame('_rebet_foo', $bag->getStorageKey());

        $bag = new FlashBag('foo', 'bar');
        $this->assertSame('bar', $bag->getStorageKey());
    }

    public function test_clear()
    {
        $src = [];
        $bag = new FlashBag('foo');
        $bag->initialize($src);
        $this->assertSame(null, $bag->peek('name'));
        $this->assertSame(null, $src['name'] ?? null);
        $bag->set('name', 'value');
        $this->assertSame('value', $bag->peek('name'));
        $this->assertSame('value', $src['name'] ?? null);
        $deleted = $bag->clear();
        $this->assertSame(null, $bag->peek('name'));
        $this->assertSame(null, $src['name'] ?? null);
        $this->assertSame('value', $deleted['name'] ?? null);
    }

    public function test_has()
    {
        $bag = new FlashBag('foo');
        $bag->set('name', 'value');
        $bag->set('map', ['a' => 'A', 'b' => 'B']);

        $this->assertFalse($bag->has('invalid'));
        $this->assertTrue($bag->has('name'));
        $this->assertTrue($bag->has('map'));
        $this->assertTrue($bag->has('map.a'));
        $this->assertFalse($bag->has('map.c'));
    }

    public function test_peek()
    {
        $bag = new FlashBag('foo');
        $bag->set('name', 'value');
        $bag->set('map', ['a' => 'A', 'b' => 'B']);

        $this->assertSame(null, $bag->peek('invalid'));
        $this->assertSame('value', $bag->peek('name'));
        $this->assertSame(['a' => 'A', 'b' => 'B'], $bag->peek('map'));
        $this->assertSame('A', $bag->peek('map.a'));
        $this->assertSame(null, $bag->peek('map.c'));
        $this->assertSame('C', $bag->peek('map.c', 'C'));
    }

    public function test_get()
    {
        $bag = new FlashBag('foo');
        $bag->set('name', 'value');
        $bag->set('map', ['a' => 'A', 'b' => 'B']);

        $this->assertSame(null, $bag->peek('invalid'));
        $this->assertSame('value', $bag->peek('name'));
        $this->assertSame('value', $bag->get('name'));
        $this->assertSame(null, $bag->peek('name'));
        $this->assertSame('A', $bag->peek('map.a'));
        $this->assertSame('A', $bag->get('map.a'));
        $this->assertSame(null, $bag->peek('map.a'));
        $this->assertSame(null, $bag->get('map.c'));
        $this->assertSame('C', $bag->get('map.c', 'C'));
        $this->assertSame(['b' => 'B'], $bag->peek('map'));
        $this->assertSame(['b' => 'B'], $bag->get('map'));
        $this->assertSame(null, $bag->peek('map'));
    }

    public function test_set()
    {
        $src = [];
        $bag = new FlashBag('foo');
        $bag->initialize($src);
        $bag->set('name', 'value');
        $bag->set('map', ['a' => 'A', 'b' => 'B']);

        $this->assertSame(null, $bag->peek('invalid'));
        $this->assertSame('value', $bag->peek('name'));
        $this->assertSame(['a' => 'A', 'b' => 'B'], $bag->peek('map'));
        $this->assertSame('A', $bag->peek('map.a'));
        $this->assertSame('A', $src['map']['a'] ?? null);
        $this->assertSame(null, $bag->peek('map.c'));
        $this->assertSame(null, $src['map']['c'] ?? null);

        $bag->set('map.c', 'C');
        $this->assertSame('C', $bag->peek('map.c'));
        $this->assertSame('C', $src['map']['c'] ?? null);
    }

    public function test_peekAll()
    {
        $bag = new FlashBag('foo');
        $bag->set('name', 'value');
        $bag->set('map', ['a' => 'A', 'b' => 'B']);
        $this->assertSame([
            'name' => 'value',
            'map'  => ['a' => 'A', 'b' => 'B']
        ], $bag->peekAll());
        $this->assertSame([
            'name' => 'value',
            'map'  => ['a' => 'A', 'b' => 'B']
        ], $bag->peekAll());
    }

    public function test_all()
    {
        $bag = new FlashBag('foo');
        $bag->set('name', 'value');
        $bag->set('map', ['a' => 'A', 'b' => 'B']);
        $this->assertSame([
            'name' => 'value',
            'map'  => ['a' => 'A', 'b' => 'B']
        ], $bag->all());
        $this->assertSame([], $bag->peekAll());
    }

    public function test_remove()
    {
        $bag = new FlashBag('foo');
        $bag->set('name', 'value');
        $bag->set('map', ['a' => 'A', 'b' => 'B']);
        $this->assertSame([
            'name' => 'value',
            'map'  => ['a' => 'A', 'b' => 'B']
        ], $bag->peekAll());

        $bag->remove('map.a');
        $this->assertSame([
            'name' => 'value',
            'map'  => ['b' => 'B']
        ], $bag->peekAll());

        $bag->remove('name');
        $this->assertSame([
            'map'  => ['b' => 'B']
        ], $bag->peekAll());

        $bag->remove('nothing');
        $this->assertSame([
            'map'  => ['b' => 'B']
        ], $bag->peekAll());
    }
}
