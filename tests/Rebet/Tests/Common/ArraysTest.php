<?php
namespace Rebet\Tests\Common;

use org\bovigo\vfs\vfsStream;
use Rebet\Common\Arrays;
use Rebet\Common\OverrideOption;
use Rebet\Tests\Mock\CountableStub;
use Rebet\Tests\Mock\Gender;
use Rebet\Tests\Mock\IteratorAggregateStub;
use Rebet\Tests\Mock\JsonSerializableStub;
use Rebet\Tests\Mock\ToArrayStub;
use Rebet\Tests\RebetTestCase;

class ArraysTest extends RebetTestCase
{
    private $root = null;

    public function setUp()
    {
        $this->root = vfsStream::setup();
        vfsStream::create(
            [
                'dummy.txt' => 'dummy'
            ],
            $this->root
        );
    }

    public function test_random()
    {
        $list = ['a', 'b', 'c', 'd', 'e', 'f'];
        sort($list);
        $size = count($list);
        for ($i=0; $i <= $size; $i++) {
            [$actual_winner, $actual_loser] = Arrays::random($list, $i);
            $this->assertSame($i, count($actual_winner));
            $this->assertSame($size - $i, count($actual_loser));
            $combined_actual = array_merge($actual_winner, $actual_loser);
            sort($combined_actual);
            $this->assertSame($list, array_values($combined_actual));
        }
    }

    public function test_isSequential()
    {
        $this->assertFalse(Arrays::isSequential(null));

        $this->assertTrue(Arrays::isSequential([]));
        $this->assertTrue(Arrays::isSequential([1]));
        $this->assertTrue(Arrays::isSequential([1, 2, 3]));
        $this->assertTrue(Arrays::isSequential([0 => 'a', 1 => 'b', 2 => 'c']));
        $this->assertTrue(Arrays::isSequential([0 => 'a', '1' => 'b']));

        $this->assertFalse(Arrays::isSequential([0 => 'a', 2 => 'c', 1 => 'b']));
        $this->assertFalse(Arrays::isSequential([1 => 'c', 2 => 'b']));
        $this->assertFalse(Arrays::isSequential([0 => 'a', 'a' => 'b']));
        $this->assertFalse(Arrays::isSequential(['a' => 'a', 'b' => 'b']));
    }

    public function test_flatten()
    {
        $this->assertNull(Arrays::flatten(null));
        $this->assertSame([], Arrays::flatten([]));
        $this->assertSame([1, 2], Arrays::flatten([1, 2]));
        $this->assertSame([1, 2, 3], Arrays::flatten([1, 2, [3]]));
        $this->assertSame([1, 2, 3, 4], Arrays::flatten([1, 2, [3, [4]]]));
        $this->assertSame([1, 2, 3, 4, 5], Arrays::flatten([1, 2, [3, [4], 5]]));
        $this->assertSame([1, 2, 3, 4, 5, 6], Arrays::flatten([1, 2, [3, [4], 5, [], 6]]));

        
        // Flat arrays are unaffected
        $array = ['#foo', '#bar', '#baz'];
        $this->assertEquals(['#foo', '#bar', '#baz'], Arrays::flatten($array));
        // Nested arrays are flattened with existing flat items
        $array = [['#foo', '#bar'], '#baz'];
        $this->assertEquals(['#foo', '#bar', '#baz'], Arrays::flatten($array));
        // Flattened array includes "null" items
        $array = [['#foo', null], '#baz', null];
        $this->assertEquals(['#foo', null, '#baz', null], Arrays::flatten($array));
        // Sets of nested arrays are flattened
        $array = [['#foo', '#bar'], ['#baz']];
        $this->assertEquals(['#foo', '#bar', '#baz'], Arrays::flatten($array));
        // Deeply nested arrays are flattened
        $array = [['#foo', ['#bar']], ['#baz']];
        $this->assertEquals(['#foo', '#bar', '#baz'], Arrays::flatten($array));
        // Nested arrays are flattened alongside arrays
        $array = [new \ArrayObject(['#foo', '#bar']), ['#baz']];
        $this->assertEquals(['#foo', '#bar', '#baz'], Arrays::flatten($array));
        // Nested arrays containing plain arrays are flattened
        $array = [new \ArrayObject(['#foo', ['#bar']]), ['#baz']];
        $this->assertEquals(['#foo', '#bar', '#baz'], Arrays::flatten($array));
        // Nested arrays containing arrays are flattened
        $array = [['#foo', new \ArrayObject(['#bar'])], ['#baz']];
        $this->assertEquals(['#foo', '#bar', '#baz'], Arrays::flatten($array));
        // Nested arrays containing arrays containing arrays are flattened
        $array = [['#foo', new \ArrayObject(['#bar', ['#zap']])], ['#baz']];
        $this->assertEquals(['#foo', '#bar', '#zap', '#baz'], Arrays::flatten($array));
    }

    public function test_pluck()
    {
        $list = [
            ['user_id' => 21, 'name' => 'John'],
            ['user_id' => 35, 'name' => 'David'],
            ['user_id' => 43, 'name' => 'Linda'],
        ];

        $this->assertSame([], Arrays::pluck(null, 'user_id'));
        $this->assertSame([], Arrays::pluck([], 'user_id'));
        $this->assertSame([21, 35, 43], Arrays::pluck($list, 'user_id'));
        $this->assertSame([21 => 'John', 35 => 'David', 43 => 'Linda'], Arrays::pluck($list, 'name', 'user_id'));
        $this->assertSame(
            [
                21 => ['user_id' => 21, 'name' => 'John'],
                35 => ['user_id' => 35, 'name' => 'David'],
                43 => ['user_id' => 43, 'name' => 'Linda']
            ],
            Arrays::pluck($list, null, 'user_id')
        );
    }
    
    public function test_override()
    {
        $this->assertSame(1, Arrays::override(null, 1));
        $this->assertNull(Arrays::override(1, null));
        $this->assertSame([1, 2], Arrays::override(1, [1, 2]));
        $this->assertSame(1, Arrays::override([1, 2], 1));
        $this->assertSame([1, 2, 3], Arrays::override([1, 2], [3]));
        $this->assertSame([3, 1, 2], Arrays::override([1, 2], [3], [], OverrideOption::PREPEND));
        $this->assertSame([3], Arrays::override([1, 2], [3], [], OverrideOption::REPLACE));
        $this->assertSame([3], Arrays::override(['a' => 1, 'b' => 2], [3]));
        $this->assertSame(
            ['a' => 3, 'b' => 2],
            Arrays::override(['a' => 1, 'b' => 2], ['a' => 3])
        );
        $this->assertSame(
            ['a' => 3, 'b' => 2, 'c' => 3],
            Arrays::override(['a' => 1, 'b' => 2], ['a' => 3, 'c' => 3])
        );
        $this->assertSame(
            ['a' => 3, 'b' => [2], 'c' => 3],
            Arrays::override(['a' => [1], 'b' => [2]], ['a' => 3, 'c' => 3])
        );
        $this->assertSame(
            ['a' => ['A' => 3, 'B' => 2, 'C' => 3], 'b' => 2, 'c' => 3],
            Arrays::override(['a' => ['A' => 1, 'B' => 2], 'b' => 2], ['a' => ['A' => 3, 'C' => 3], 'c' => 3])
        );
        $this->assertSame(
            ['a' => ['A' => ['α' => 1], 'B' => 2, 'C' => 3], 'b' => 2, 'c' => 3],
            Arrays::override(['a' => ['A' => 1, 'B' => 2], 'b' => 2], ['a' => ['A' => ['α' => 1], 'C' => 3], 'c' => 3])
        );
        $this->assertSame(
            ['a' => ['A' => 1, 'B' => 2], 'b' => 2, 'c' => 3],
            Arrays::override(['a' => ['A' => 1, 'B' => 2], 'b' => 2], ['a' => [], 'c' => 3])
        );
        $this->assertSame(
            ['a' => [], 'b' => 2, 'c' => 3],
            Arrays::override(['a' => ['A' => 1, 'B' => 2], 'b' => 2], ['a!' => [], 'c' => 3])
        );
        $this->assertSame(
            ['a' => [['A' => 1], ['B' => 2], ['A' => 3]], 'b' => 2, 'c' => 3],
            Arrays::override(['a' => [['A' => 1], ['B' => 2]], 'b' => 2], ['a' => [['A' => 3]], 'c' => 3])
        );

        $this->assertSame(
            [
                'map'   => ['a' => ['A' => 'A', 'B' => 'B'], 'b' => 'b', 'c' => 'C'],
                'array' => ['a', 'b', 'c'],
            ],
            Arrays::override(
                [
                    'map'   => ['a' => ['A' => 'A'], 'b' => 'b'],
                    'array' => ['a', 'b'],
                ],
                [
                    'map'   => ['a' => ['B' => 'B'], 'c' => 'C'],
                    'array' => ['c'],
                ]
            )
        );

        $merged = Arrays::override(new \ArrayObject([1, 2]), [3]);
        $this->assertFalse(is_array($merged));
        $this->assertInstanceOf(\ArrayObject::class, $merged);
        $this->assertEquals(new \ArrayObject([1, 2, 3]), $merged);

        $merged = Arrays::override(new \ArrayObject([1, 2]), new \ArrayObject([3]));
        $this->assertFalse(is_array($merged));
        $this->assertInstanceOf(\ArrayObject::class, $merged);
        $this->assertEquals(new \ArrayObject([1, 2, 3]), $merged);

        $merged = Arrays::override([1, 2], new \ArrayObject([3]));
        $this->assertTrue(is_array($merged));
        $this->assertEquals([1, 2, 3], $merged);


        $merged = Arrays::override(new \ArrayObject([1, 2]), [3], [], OverrideOption::PREPEND);
        $this->assertFalse(is_array($merged));
        $this->assertInstanceOf(\ArrayObject::class, $merged);
        $this->assertEquals(new \ArrayObject([3, 1, 2]), $merged);

        $merged = Arrays::override(new \ArrayObject([1, 2]), new \ArrayObject([3]), [], OverrideOption::PREPEND);
        $this->assertFalse(is_array($merged));
        $this->assertInstanceOf(\ArrayObject::class, $merged);
        $this->assertEquals(new \ArrayObject([3, 1, 2]), $merged);

        $merged = Arrays::override([1, 2], new \ArrayObject([3]), [], OverrideOption::PREPEND);
        $this->assertTrue(is_array($merged));
        $this->assertEquals([3, 1, 2], $merged);
    }

    public function test_override_option()
    {
        $this->assertSame(
            [
                'map'   => ['a' => ['B' => 'B'], 'b' => 'b', 'c' => 'C'],
                'array' => ['c'],
            ],
            Arrays::override(
                [
                    'map'   => ['a' => ['A' => 'A'], 'b' => 'b'],
                    'array' => ['a', 'b'],
                ],
                [
                    'map'   => ['a' => ['B' => 'B'], 'c' => 'C'],
                    'array' => ['c'],
                ],
                [
                    'map'   => ['a' => OverrideOption::REPLACE],
                    'array' => OverrideOption::REPLACE,
                ]
            )
        );
    }

    public function test_override_optionInline()
    {
        $this->assertSame(
            [
                'map'   => ['a' => ['B' => 'B'], 'b' => 'b', 'c' => 'C'],
                'array' => ['c'],
            ],
            Arrays::override(
                [
                    'map'   => ['a' => ['A' => 'A'], 'b' => 'b'],
                    'array' => ['a', 'b'],
                ],
                [
                    'map'    => ['a!' => ['B' => 'B'], 'c' => 'C'],
                    'array!' => ['c'],
                ]
            )
        );
    }

    public function test_override_defaultModePrepend()
    {
        $this->assertSame(
            [
                'array' => ['c', 'a', 'b'],
            ],
            Arrays::override(
                [
                    'array' => ['a', 'b'],
                ],
                [
                    'array' => ['c'],
                ],
                [],
                OverrideOption::PREPEND
            )
        );

        $this->assertSame(
            [
                'array' => ['c', 'a', 'b'],
            ],
            Arrays::override(
                [
                    'array' => ['a', 'b'],
                ],
                [
                    'array<' => ['c'],
                ],
                [],
                OverrideOption::PREPEND
            )
        );

        $this->assertSame(
            [
                'array' => ['a', 'b', 'c'],
            ],
            Arrays::override(
                [
                    'array' => ['a', 'b'],
                ],
                [
                    'array>' => ['c'],
                ],
                [],
                OverrideOption::PREPEND
            )
        );

        $this->assertSame(
            [
                'array' => ['c'],
            ],
            Arrays::override(
                [
                    'array' => ['a', 'b'],
                ],
                [
                    'array!' => ['c'],
                ],
                [],
                OverrideOption::PREPEND
            )
        );
    }

    public function test_override_defaultModeReplace()
    {
        $this->assertSame(
            [
                'array' => ['c'],
            ],
            Arrays::override(
                [
                    'array' => ['a', 'b'],
                ],
                [
                    'array' => ['c'],
                ],
                [],
                OverrideOption::REPLACE
            )
        );
    }

    public function test_duplicate()
    {
        $this->assertNull(Arrays::duplicate(null));
        $this->assertSame([], Arrays::duplicate([]));
        $this->assertSame([], Arrays::duplicate([1, 2, 3]));
        $this->assertSame([1, 3, 'a'], Arrays::duplicate(
            [1, 2, 3, '1', 3, 'a', 'b', 'c', 'a', 'a', 'B']
        ));
    }
    
    public function test_shuffleWithSeed()
    {
        $this->assertNull(Arrays::shuffle(null, 1234));
        $this->assertEquals([], Arrays::shuffle([], 1234));
        
        $this->assertEquals(
            Arrays::shuffle(range(0, 100, 10), 1234),
            Arrays::shuffle(range(0, 100, 10), 1234)
        );
    }

    public function test_pull()
    {
        $array = ['name' => 'Desk', 'price' => 100];
        $name  = Arrays::pull($array, 'name');
        $this->assertEquals('Desk', $name);
        $this->assertEquals(['price' => 100], $array);
        // Works on first level keys with out nest
        $array = ['joe@example.com' => 'Joe', 'jane@localhost' => 'Jane'];
        $name  = Arrays::pull($array, 'joe@example.com');
        $this->assertEquals('Joe', $name);
        $this->assertEquals(['jane@localhost' => 'Jane'], $array);
        // Works on nested last level keys
        $array = ['emails' => ['joe@example.com' => 'Joe', 'jane@localhost' => 'Jane']];
        $name  = Arrays::pull($array, 'emails.joe@example.com');
        $this->assertEquals('Joe', $name);
        $this->assertEquals(['emails' => ['joe@example.com' => 'Joe', 'jane@localhost' => 'Jane']], $array);
        // Does not work for nested middle keys
        $array = ['joe@example.com' => ['name' => 'Joe']];
        $name  = Arrays::pull($array, 'joe@example.com.name');
        $this->assertNull($name);
        $this->assertEquals(['joe@example.com' => ['name' => 'Joe']], $array);
    }
    
    public function test_prepend()
    {
        $array = Arrays::prepend(['one', 'two', 'three', 'four'], 'zero');
        $this->assertEquals(['zero', 'one', 'two', 'three', 'four'], $array);
        $array = Arrays::prepend(['one' => 1, 'two' => 2], 0, 'zero');
        $this->assertEquals(['zero' => 0, 'one' => 1, 'two' => 2], $array);
    }
    
    public function test_only()
    {
        $array = ['name' => 'Desk', 'price' => 100, 'orders' => 10];
        $array = Arrays::only($array, ['name', 'price']);
        $this->assertEquals(['name' => 'Desk', 'price' => 100], $array);
    }

    public function test_last()
    {
        $array = [100, 200, 300];
        $last  = Arrays::last($array, function ($value) {
            return $value < 250;
        });
        $this->assertEquals(200, $last);
        $last = Arrays::last($array, function ($value, $key) {
            return $key < 2;
        });
        $this->assertEquals(200, $last);
        $this->assertEquals(300, Arrays::last($array));
    }

    public function test_first()
    {
        $array = [100, 200, 300];
        $value = Arrays::first($array, function ($value) {
            return $value >= 150;
        });
        $this->assertEquals(200, $value);
        $this->assertEquals(100, Arrays::first($array));
    }

    public function test_where()
    {
        $array = [100, '200', 300, '400', 500];
        $array = Arrays::where($array, null);
        $this->assertEquals([100, '200', 300, '400', 500], $array);

        $array = [100, '200', 300, '400', 500];
        $array = Arrays::where($array, function ($value, $key) {
            return is_string($value);
        });
        $this->assertEquals([1 => 200, 3 => 400], $array);
    }

    public function test_compact()
    {
        $array = [0, null, [], '', false, 500];
        $array = Arrays::compact($array);
        $this->assertEquals([0 => 0, 4 => false, 5=> 500], $array);
    }

    public function test_forget()
    {
        $array = ['products' => ['desk' => ['price' => 100]]];
        Arrays::forget($array, null);
        $this->assertEquals(['products' => ['desk' => ['price' => 100]]], $array);
        $array = ['products' => ['desk' => ['price' => 100]]];
        Arrays::forget($array, []);
        $this->assertEquals(['products' => ['desk' => ['price' => 100]]], $array);
        $array = ['products' => ['desk' => ['price' => 100]]];
        Arrays::forget($array, 'products.desk');
        $this->assertEquals(['products' => []], $array);
        $array = ['products' => ['desk' => ['price' => 100]]];
        Arrays::forget($array, 'products.desk.price');
        $this->assertEquals(['products' => ['desk' => []]], $array);
        $array = ['products' => ['desk' => ['price' => 100]]];
        Arrays::forget($array, 'products.final.price');
        $this->assertEquals(['products' => ['desk' => ['price' => 100]]], $array);
        $array = ['shop' => ['cart' => [150 => 0]]];
        Arrays::forget($array, 'shop.final.cart');
        $this->assertEquals(['shop' => ['cart' => [150 => 0]]], $array);
        $array = ['products' => ['desk' => ['price' => ['original' => 50, 'taxes' => 60]]]];
        Arrays::forget($array, 'products.desk.price.taxes');
        $this->assertEquals(['products' => ['desk' => ['price' => ['original' => 50]]]], $array);
        $array = ['products' => ['desk' => ['price' => ['original' => 50, 'taxes' => 60]]]];
        Arrays::forget($array, 'products.desk.final.taxes');
        $this->assertEquals(['products' => ['desk' => ['price' => ['original' => 50, 'taxes' => 60]]]], $array);
        $array = ['products' => ['desk' => ['price' => 50], null => 'something']];
        Arrays::forget($array, ['products.amount.all', 'products.desk.price']);
        $this->assertEquals(['products' => ['desk' => [], null => 'something']], $array);
        // Only works on first level keys
        $array = ['joe@example.com' => 'Joe', 'jane@example.com' => 'Jane'];
        Arrays::forget($array, 'joe@example.com');
        $this->assertEquals(['jane@example.com' => 'Jane'], $array);
        // Does not work for nested keys
        $array = ['emails' => ['joe@example.com' => ['name' => 'Joe'], 'jane@localhost' => ['name' => 'Jane']]];
        Arrays::forget($array, ['emails.joe@example.com', 'emails.jane@localhost']);
        $this->assertEquals(['emails' => ['joe@example.com' => ['name' => 'Joe']]], $array);
    }

    public function test_except()
    {
        $array = ['name' => 'Desk', 'price' => 100];
        $array = Arrays::except($array, ['price']);
        $this->assertEquals(['name' => 'Desk'], $array);
    }

    public function test_exists()
    {
        $this->assertTrue(Arrays::exists([1], 0));
        $this->assertTrue(Arrays::exists([null], 0));
        $this->assertTrue(Arrays::exists(['a' => 1], 'a'));
        $this->assertTrue(Arrays::exists(['a' => null], 'a'));
        $this->assertTrue(Arrays::exists(new \ArrayObject(['a' => null]), 'a'));
        $this->assertFalse(Arrays::exists([1], 1));
        $this->assertFalse(Arrays::exists([null], 1));
        $this->assertFalse(Arrays::exists(['a' => 1], 0));
        $this->assertFalse(Arrays::exists(new \ArrayObject(['a' => null]), 'b'));
    }

    public function test_crossJoin()
    {
        // Single dimension
        $this->assertSame(
            [[1, 'a'], [1, 'b'], [1, 'c']],
            Arrays::crossJoin([1], ['a', 'b', 'c'])
        );
        // Square matrix
        $this->assertSame(
            [[1, 'a'], [1, 'b'], [2, 'a'], [2, 'b']],
            Arrays::crossJoin([1, 2], ['a', 'b'])
        );
        // Rectangular matrix
        $this->assertSame(
            [[1, 'a'], [1, 'b'], [1, 'c'], [2, 'a'], [2, 'b'], [2, 'c']],
            Arrays::crossJoin([1, 2], ['a', 'b', 'c'])
        );
        // 3D matrix
        $this->assertSame(
            [
                [1, 'a', 'I'], [1, 'a', 'II'], [1, 'a', 'III'],
                [1, 'b', 'I'], [1, 'b', 'II'], [1, 'b', 'III'],
                [2, 'a', 'I'], [2, 'a', 'II'], [2, 'a', 'III'],
                [2, 'b', 'I'], [2, 'b', 'II'], [2, 'b', 'III'],
            ],
            Arrays::crossJoin([1, 2], ['a', 'b'], ['I', 'II', 'III'])
        );
        // With 1 empty dimension
        $this->assertEmpty(Arrays::crossJoin([], ['a', 'b'], ['I', 'II', 'III']));
        $this->assertEmpty(Arrays::crossJoin([1, 2], [], ['I', 'II', 'III']));
        $this->assertEmpty(Arrays::crossJoin([1, 2], ['a', 'b'], []));
        // With empty arrays
        $this->assertEmpty(Arrays::crossJoin([], [], []));
        $this->assertEmpty(Arrays::crossJoin([], []));
        $this->assertEmpty(Arrays::crossJoin([]));
        // Not really a proper usage, still, test for preserving BC
        $this->assertSame([[]], Arrays::crossJoin());
    }
    
    public function test_collapse()
    {
        $data = [['foo', 'bar'], ['baz']];
        $this->assertEquals(['foo', 'bar', 'baz'], Arrays::collapse($data));
    }

    public function test_count()
    {
        $this->assertSame(0, Arrays::count(null));
        $this->assertSame(1, Arrays::count(''));
        $this->assertSame(1, Arrays::count(0));
        $this->assertSame(0, Arrays::count([]));
        $this->assertSame(3, Arrays::count([1, 2, 3]));
        $this->assertSame(4, Arrays::count(new CountableStub(4)));
        $this->assertSame(5, Arrays::count(new IteratorAggregateStub([1, 2, 3, 4, 5])));
    }

    public function test_toArray()
    {
        $this->assertNull(Arrays::toArray(null));

        $this->assertSame([], Arrays::toArray([]));
        $this->assertSame([1, 2], Arrays::toArray([1, 2]));
        $this->assertSame(['a' => 'A'], Arrays::toArray(['a' => 'A']));

        $this->assertSame([''], Arrays::toArray(''));
        $this->assertSame(['a'], Arrays::toArray('a'));
        $this->assertSame(['a,b,c'], Arrays::toArray('a,b,c'));

        $to_array = new ToArrayStub([1, 2, 'a' => 'A']);
        $this->assertSame([1, 2, 'a' => 'A'], Arrays::toArray($to_array));

        $travers = new \ArrayObject([1, 2, 'a' => 'A']);
        $this->assertSame([1, 2, 'a' => 'A'], Arrays::toArray($travers));

        $jsonValue = Gender::MALE();
        $this->assertSame([$jsonValue], Arrays::toArray($jsonValue));
        $jsonValue = new JsonSerializableStub('abc');
        $this->assertSame([$jsonValue], Arrays::toArray($jsonValue));

        $jsonArray = new JsonSerializableStub([1, 2, 'a' => 'A']);
        $this->assertSame([1, 2, 'a' => 'A'], Arrays::toArray($jsonArray));

        $this->assertSame([1], Arrays::toArray(1));
        $this->assertSame([1.2], Arrays::toArray(1.2));
        $this->assertSame([true], Arrays::toArray(true));

        $resource = fopen('vfs://root/dummy.txt', 'r');
        $this->assertSame([$resource], Arrays::toArray($resource));
        fclose($resource);
    }
    
    public function test_diff()
    {
        $array = ['id' => 1, 'first_word' => 'Hello'];
        $items = ['first_word' => 'Hello', 'last_word' => 'World'];
        $this->assertEquals(['id' => 1], Arrays::diff($array, $items));
        $this->assertEquals(['id' => 1], Arrays::diff($array, new \ArrayObject($items)));
        
        $this->assertEquals(['id' => 1, 'first_word' => 'Hello'], Arrays::diff($array, null));
        
        
        $array = ['en_GB', 'fr', 'HR'];
        $items = ['en_gb', 'hr'];
        $this->assertEquals(['en_GB', 'fr', 'HR'], Arrays::diff($array, $items));
        $this->assertEquals([1 => 'fr'], Arrays::diff($array, $items, 'strcasecmp'));

        $this->assertEquals(['en_GB', 'fr', 'HR'], Arrays::diff($array, null, 'strcasecmp'));
    }
}
