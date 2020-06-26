<?php
namespace Rebet\Tests\Common;

use Rebet\Common\Arrayable;
use Rebet\DateTime\DateTime;
use Rebet\Tests\RebetTestCase;

class ArrayableTest extends RebetTestCase
{
    private $empty;
    private $array;
    private $map;

    public function setUp()
    {
        parent::setUp();
        $this->empty = new ArrayableTest_Mock();
        $this->array = new ArrayableTest_Mock([1, 2, 3, 4, 5]);
        $this->map   = new ArrayableTest_Mock(['a' => 'A', 'b' => 'B', 'c' => 'C']);
    }

    public function test_all()
    {
        $this->assertSame([], $this->empty->all());
        $this->assertSame([1, 2, 3, 4, 5], $this->array->all());
        $this->assertSame(['a' => 'A', 'b' => 'B', 'c' => 'C'], $this->map->all());
    }

    public function test_empty()
    {
        $this->assertSame(true, $this->empty->empty());
        $this->assertSame(false, $this->array->empty());
        $this->assertSame(false, $this->map->empty());
    }

    public function test_has()
    {
        $this->assertSame(false, $this->empty->has('a'));
        $this->assertSame(false, $this->array->has('a'));
        $this->assertSame(true, $this->array->has(1));
        $this->assertSame(false, $this->array->has(10));
        $this->assertSame(true, $this->map->has('a'));
        $this->assertSame(false, $this->map->has('d'));

        $map  = new ArrayableTest_Mock(['a' => 'A', 'b' => 'B', 'c' => null]);
        $this->assertSame(true, $map->has('c'));
    }

    public function test_count()
    {
        $this->assertSame(0, $this->empty->count());
        $this->assertSame(5, $this->array->count());
        $this->assertSame(3, $this->map->count());
    }

    public function test_getIterator()
    {
        $this->assertInstanceOf(\ArrayIterator::class, $this->empty->getIterator());
        $this->assertInstanceOf(\ArrayIterator::class, $this->array->getIterator());
        $this->assertInstanceOf(\ArrayIterator::class, $this->map->getIterator());
    }

    public function test_offsetSet()
    {
        $this->assertSame([], $this->empty->all());
        $this->empty->offsetSet(null, 'a');
        $this->assertSame(['a'], $this->empty->all());
        $this->empty[] = 'b';
        $this->assertSame(['a', 'b'], $this->empty->all());
        $this->empty['c'] = 'C';
        $this->assertSame(['a', 'b', 'c' => 'C'], $this->empty->all());
        $this->empty->offsetSet('d', 'D');
        $this->assertSame(['a', 'b', 'c' => 'C', 'd' => 'D'], $this->empty->all());
        $this->empty['c'] = 'cc';
        $this->assertSame(['a', 'b', 'c' => 'cc', 'd' => 'D'], $this->empty->all());
    }

    public function test_offsetExists()
    {
        $this->assertFalse($this->empty->offsetExists(0));
        $this->assertTrue($this->array->offsetExists(0));
        $this->assertFalse($this->array->offsetExists(6));
        $this->assertTrue($this->map->offsetExists('a'));
    }

    public function test_offsetUnset()
    {
        $this->assertSame([1, 2, 3, 4, 5], $this->array->all());
        $this->array->offsetUnset(0);
        $this->assertSame([1 => 2, 2 => 3, 3 => 4, 4 => 5], $this->array->all());
        unset($this->array[3]);
        $this->assertSame([1 => 2, 2 => 3, 4 => 5], $this->array->all());
    }

    public function test_offsetGet()
    {
        $this->assertSame(3, $this->array->offsetGet(2));
        $this->assertSame(3, $this->array[2]);
        $this->assertSame(null, $this->array['a']);
        $this->assertSame('A', $this->map['a']);
    }

    public function test_toArray()
    {
        $this->assertSame([], $this->empty->toArray());
        $this->assertSame([1, 2, 3, 4, 5], $this->array->toArray());
        $this->assertSame(['a' => 'A', 'b' => 'B', 'c' => 'C'], $this->map->toArray());

        $this->array[]      = $this->empty;
        $this->map['array'] = $this->array;
        $this->assertSame(['a' => 'A', 'b' => 'B', 'c' => 'C', 'array' => [1, 2, 3, 4, 5, []]], $this->map->toArray());
    }

    public function test_jsonSerialize()
    {
        $this->assertSame([], $this->empty->jsonSerialize());
        $this->assertSame([1, 2, 3, 4, 5], $this->array->jsonSerialize());
        $this->assertSame(['a' => 'A', 'b' => 'B', 'c' => 'C'], $this->map->jsonSerialize());

        $datetime      = DateTime::createDateTime('2010-01-02 12:34:56');
        $this->array[] = $datetime;
        $this->assertSame([1, 2, 3, 4, 5, $datetime], $this->array->toArray());
        $this->assertSame([1, 2, 3, 4, 5, '2010-01-02 12:34:56'], $this->array->jsonSerialize());
    }
}

class ArrayableTest_Mock implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable
{
    use Arrayable;

    protected $container  = [];

    public function __construct(array $array = [])
    {
        $this->container = $array;
    }

    protected function &container() : array
    {
        return $this->container;
    }
}
