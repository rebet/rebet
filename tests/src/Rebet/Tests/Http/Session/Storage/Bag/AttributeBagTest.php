<?php
namespace Rebet\Tests\Http\Session\Storage\Bag;

use Rebet\Http\Session\Storage\Bag\AttributeBag;
use Rebet\Tests\RebetTestCase;

class AttributeBagTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(AttributeBag::class, new AttributeBag('test'));
    }

    public function test_getName()
    {
        $bag = new AttributeBag('foo');
        $this->assertSame('foo', $bag->getName());
    }

    public function test_initialize()
    {
        $src = [];
        $bag = new AttributeBag('foo');
        $bag->initialize($src);
        $this->assertSame(null, $bag->get('name'));
        $this->assertSame(null, $src['name'] ?? null);
        $bag->set('name', 'value');
        $this->assertSame('value', $bag->get('name'));
        $this->assertSame('value', $src['name'] ?? null);
    }

    public function test_getStorageKey()
    {
        $bag = new AttributeBag('foo');
        $this->assertSame('_rebet_foo', $bag->getStorageKey());

        $bag = new AttributeBag('foo', 'bar');
        $this->assertSame('bar', $bag->getStorageKey());
    }

    public function test_clear()
    {
        $src = [];
        $bag = new AttributeBag('foo');
        $bag->initialize($src);
        $this->assertSame(null, $bag->get('name'));
        $this->assertSame(null, $src['name'] ?? null);
        $bag->set('name', 'value');
        $this->assertSame('value', $bag->get('name'));
        $this->assertSame('value', $src['name'] ?? null);
        $deleted = $bag->clear();
        $this->assertSame(null, $bag->get('name'));
        $this->assertSame(null, $src['name'] ?? null);
        $this->assertSame('value', $deleted['name'] ?? null);
    }

    public function test_has()
    {
        $bag = new AttributeBag('foo');
        $bag->set('name', 'value');
        $bag->set('map', ['a' => 'A', 'b' => 'B']);

        $this->assertFalse($bag->has('invalid'));
        $this->assertTrue($bag->has('name'));
        $this->assertTrue($bag->has('map'));
        $this->assertTrue($bag->has('map.a'));
        $this->assertFalse($bag->has('map.c'));
    }

    public function test_get()
    {
        $bag = new AttributeBag('foo');
        $bag->set('name', 'value');
        $bag->set('map', ['a' => 'A', 'b' => 'B']);

        $this->assertSame(null, $bag->get('invalid'));
        $this->assertSame('value', $bag->get('name'));
        $this->assertSame(['a' => 'A', 'b' => 'B'], $bag->get('map'));
        $this->assertSame('A', $bag->get('map.a'));
        $this->assertSame(null, $bag->get('map.c'));
        $this->assertSame('C', $bag->get('map.c', 'C'));
    }

    public function test_set()
    {
        $src = [];
        $bag = new AttributeBag('foo');
        $bag->initialize($src);
        $bag->set('name', 'value');
        $bag->set('map', ['a' => 'A', 'b' => 'B']);

        $this->assertSame(null, $bag->get('invalid'));
        $this->assertSame('value', $bag->get('name'));
        $this->assertSame(['a' => 'A', 'b' => 'B'], $bag->get('map'));
        $this->assertSame('A', $bag->get('map.a'));
        $this->assertSame('A', $src['map']['a'] ?? null);
        $this->assertSame(null, $bag->get('map.c'));
        $this->assertSame(null, $src['map']['c'] ?? null);

        $bag->set('map.c', 'C');
        $this->assertSame('C', $bag->get('map.c'));
        $this->assertSame('C', $src['map']['c'] ?? null);
    }

    public function test_all()
    {
        $bag = new AttributeBag('foo');
        $bag->set('name', 'value');
        $bag->set('map', ['a' => 'A', 'b' => 'B']);
        $this->assertSame([
            'name' => 'value',
            'map'  => ['a' => 'A', 'b' => 'B']
        ], $bag->all());
    }

    public function test_remove()
    {
        $bag = new AttributeBag('foo');
        $bag->set('name', 'value');
        $bag->set('map', ['a' => 'A', 'b' => 'B']);
        $this->assertSame([
            'name' => 'value',
            'map'  => ['a' => 'A', 'b' => 'B']
        ], $bag->all());

        $bag->remove('map.a');
        $this->assertSame([
            'name' => 'value',
            'map'  => ['b' => 'B']
        ], $bag->all());

        $bag->remove('name');
        $this->assertSame([
            'map'  => ['b' => 'B']
        ], $bag->all());

        $bag->remove('nothing');
        $this->assertSame([
            'map'  => ['b' => 'B']
        ], $bag->all());
    }

    public function test_getIterator()
    {
        $bag = new AttributeBag('foo');
        $this->assertInstanceOf(\ArrayIterator::class, $bag->getIterator());
        $bag->set('a', 'A');
        $bag->set('b', 'B');
        $keys   = ['a', 'b'];
        $values = ['A', 'B'];
        $i      = 0;
        foreach ($bag as $key => $value) {
            $this->assertSame($keys[$i], $key);
            $this->assertSame($values[$i], $value);
            $i++;
        }
    }

    public function test_count()
    {
        $bag = new AttributeBag('foo');
        $this->assertSame(0, $bag->count());
        $bag->set('a', 'A');
        $bag->set('b', 'B');
        $this->assertSame(2, $bag->count());
    }
}
