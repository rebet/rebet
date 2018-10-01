<?php
namespace Rebet\Tests\Common;

use Rebet\Tests\RebetTestCase;
use Rebet\Common\DotAccessDelegator;
use Rebet\Common\Reflector;
use Rebet\Common\Utils;
use Rebet\Common\Enum;
use org\bovigo\vfs\vfsStream;

class ReflectorTest extends RebetTestCase
{
    private $array       = null;
    private $map         = null;
    private $objectect      = null;
    private $transparent = null;

    public function setUp()
    {
        $this->array  = ['a','b','c', null];
        $this->map    = [
            'name' => 'John Smith',
            'gender' => 'male',
            'hobbies' => ['game', 'outdoor'],
            'partner' => [
                'name' => 'Jane Smith',
                'gender' => 'female'
            ],
            'children' => null
        ];
        $this->object = (object) [
            'name' => 'John Smith',
            'gender' => 'male',
            'hobbies' => ['game', 'outdoor'],
            'partner' => (object)[
                'name' => 'Jane Smith',
                'gender' => 'female'
            ],
            'children' => null
        ];
        $this->transparent = (object) [
            'a' => new class() implements DotAccessDelegator {
                public function get()
                {
                    return [
                        'b' => 'ab',
                        'c' => new class() implements DotAccessDelegator {
                            public function get()
                            {
                                return 'ac';
                            }
                        },
                    ];
                }
            },
            'b' => new class() implements DotAccessDelegator {
                public function get()
                {
                    return new class() implements DotAccessDelegator {
                        public function get()
                        {
                            return 'b';
                        }
                    };
                }
            }
        ];

        $this->root = vfsStream::setup();
        vfsStream::create(
            [
                'dummy.txt' => 'dummy'
            ],
            $this->root
        );
    }

    public function test_instantiate()
    {
        $this->assertNull(Reflector::instantiate(null));
        $this->assertNull(Reflector::instantiate(''));
        $this->assertNull(Reflector::instantiate([]));

        $this->assertSame('default', Reflector::instantiate(ReflectorTest_Mock::class)->value);
        $this->assertSame('via getInstance()', Reflector::instantiate(ReflectorTest_Mock::class. '@getInstance')->value);
        $this->assertSame('callable', Reflector::instantiate(function () {
            return 'callable';
        }));
        $this->assertSame('arg', Reflector::instantiate([ReflectorTest_Mock::class, 'arg'])->value);
        $this->assertSame('arg via build()', Reflector::instantiate([ReflectorTest_Mock::class. '@build', 'arg'])->value);
        $this->assertSame('arg via callable', Reflector::instantiate([function ($v) {
            return $v.' via callable';
        }, 'arg']));
        $this->assertSame(123, Reflector::instantiate(123));
        $this->assertSame('instantiated', Reflector::instantiate(new ReflectorTest_Mock('instantiated'))->value);
    }

    public function test_get()
    {
        $this->assertSame($this->array, Reflector::get($this->array, null));
        $this->assertSame($this->array, Reflector::get($this->array, ''));
        $this->assertNull(Reflector::get($this->array, 'invalid_key'));
        $this->assertSame('default', Reflector::get($this->array, 'invalid_key', 'default'));
        $this->assertSame('a', Reflector::get($this->array, 0, 'default'));
        $this->assertSame('default', Reflector::get($this->array, 3, 'default'));
        $this->assertSame('default', Reflector::get($this->array, 4, 'default'));

        $this->assertSame($this->map, Reflector::get($this->map, null));
        $this->assertSame($this->map, Reflector::get($this->map, ''));
        $this->assertSame('default', Reflector::get($this->map, 'invalid_key', 'default'));
        $this->assertSame('John Smith', Reflector::get($this->map, 'name', 'default'));
        $this->assertSame(['game', 'outdoor'], Reflector::get($this->map, 'hobbies', []));
        $this->assertSame('game', Reflector::get($this->map, 'hobbies.0', 'default'));
        $this->assertSame('default', Reflector::get($this->map, 'hobbies.3', 'default'));
        $this->assertSame('default', Reflector::get($this->map, 'hobbies.invalid_key', 'default'));
        $this->assertSame('Jane Smith', Reflector::get($this->map, 'partner.name', 'default'));
        $this->assertSame('female', Reflector::get($this->map, 'partner.gender', 'default'));
        $this->assertSame('default', Reflector::get($this->map, 'partner.invalid_key', 'default'));
        $this->assertSame('default', Reflector::get($this->map, 'partner.gender.invalid_key', 'default'));
        $this->assertSame([], Reflector::get($this->map, 'children', []));

        $this->assertSame($this->object, Reflector::get($this->object, null));
        $this->assertSame($this->object, Reflector::get($this->object, ''));
        $this->assertSame('default', Reflector::get($this->object, 'invalid_key', 'default'));
        $this->assertSame('John Smith', Reflector::get($this->object, 'name', 'default'));
        $this->assertSame(['game', 'outdoor'], Reflector::get($this->object, 'hobbies', []));
        $this->assertSame('game', Reflector::get($this->object, 'hobbies.0', 'default'));
        $this->assertSame('default', Reflector::get($this->object, 'hobbies.3', 'default'));
        $this->assertSame('default', Reflector::get($this->object, 'hobbies.invalid_key', 'default'));
        $this->assertSame('Jane Smith', Reflector::get($this->object, 'partner.name', 'default'));
        $this->assertSame('female', Reflector::get($this->object, 'partner.gender', 'default'));
        $this->assertSame('default', Reflector::get($this->object, 'partner.invalid_key', 'default'));
        $this->assertSame('default', Reflector::get($this->object, 'partner.gender.invalid_key', 'default'));
        $this->assertSame([], Reflector::get($this->object, 'children', []));

        $this->assertSame(
            [
                'b' => 'ab',
                'c' => 'ac',
            ],
            Reflector::get($this->transparent->a, null)
        );
        $this->assertSame('b', Reflector::get($this->transparent->b, null));
        $this->assertSame('b', Reflector::get($this->transparent->b, ''));
        $this->assertSame('ab', Reflector::get($this->transparent, 'a.b'));
        $this->assertSame('ac', Reflector::get($this->transparent, 'a.c'));
        $this->assertSame('b', Reflector::get($this->transparent, 'b'));
    }

    public function test_has()
    {
        $this->assertFalse(Reflector::has(null, 'invalid_key'));
        $this->assertFalse(Reflector::has('string', 'invalid_key'));

        $this->assertFalse(Reflector::has($this->array, 'invalid_key'));
        $this->assertTrue(Reflector::has($this->array, 0));
        $this->assertTrue(Reflector::has($this->array, 3));
        $this->assertFalse(Reflector::has($this->array, 4));

        $this->assertFalse(Reflector::has($this->map, 'invalid_key'));
        $this->assertTrue(Reflector::has($this->map, 'name'));
        $this->assertTrue(Reflector::has($this->map, 'hobbies'));
        $this->assertTrue(Reflector::has($this->map, 'hobbies.0'));
        $this->assertFalse(Reflector::has($this->map, 'hobbies.3'));
        $this->assertFalse(Reflector::has($this->map, 'hobbies.invalid_key'));
        $this->assertTrue(Reflector::has($this->map, 'partner.name'));
        $this->assertTrue(Reflector::has($this->map, 'partner.gender'));
        $this->assertFalse(Reflector::has($this->map, 'partner.invalid_key'));
        $this->assertFalse(Reflector::has($this->map, 'partner.gender.invalid_key'));
        $this->assertTrue(Reflector::has($this->map, 'children'));
        $this->assertFalse(Reflector::has($this->map, 'children.0'));

        $this->assertFalse(Reflector::has($this->object, 'invalid_key'));
        $this->assertTrue(Reflector::has($this->object, 'name'));
        $this->assertTrue(Reflector::has($this->object, 'hobbies'));
        $this->assertTrue(Reflector::has($this->object, 'hobbies.0'));
        $this->assertFalse(Reflector::has($this->object, 'hobbies.3'));
        $this->assertFalse(Reflector::has($this->object, 'hobbies.invalid_key'));
        $this->assertTrue(Reflector::has($this->object, 'partner.name'));
        $this->assertTrue(Reflector::has($this->object, 'partner.gender'));
        $this->assertFalse(Reflector::has($this->object, 'partner.invalid_key'));
        $this->assertFalse(Reflector::has($this->object, 'partner.gender.invalid_key'));
        $this->assertTrue(Reflector::has($this->object, 'children'));
        $this->assertFalse(Reflector::has($this->object, 'children.0'));

        $this->assertTrue(Reflector::has($this->transparent, 'a'));
        $this->assertTrue(Reflector::has($this->transparent, 'a.b'));
        $this->assertFalse(Reflector::has($this->transparent, 'a.b.c'));
        $this->assertTrue(Reflector::has($this->transparent, 'a.c'));
        $this->assertFalse(Reflector::has($this->transparent, 'a.c.d'));
        $this->assertFalse(Reflector::has($this->transparent, 'a.d'));
        $this->assertTrue(Reflector::has($this->transparent, 'b'));
        $this->assertFalse(Reflector::has($this->transparent, 'b.c'));
    }

    public function test_set()
    {
        Reflector::set($this->array, 0, 'A');
        $this->assertSame('A', $this->array[0]);

        Reflector::set($this->array, '1', 'B');
        $this->assertSame('B', $this->array[1]);


        Reflector::set($this->map, 'name', 'Charles Babbage');
        $this->assertSame('Charles Babbage', $this->map['name']);

        Reflector::set($this->map, 'hobbies.0', 'cycling');
        $this->assertSame('cycling', $this->map['hobbies'][0]);
        $this->assertSame(['cycling','outdoor'], $this->map['hobbies']);

        Reflector::set($this->map, 'hobbies', ['game']);
        $this->assertSame('game', $this->map['hobbies'][0]);
        $this->assertSame(['game'], $this->map['hobbies']);

        Reflector::set($this->map, 'partner.name', 'Georgiana Whitmore');
        $this->assertSame('Georgiana Whitmore', $this->map['partner']['name']);


        Reflector::set($this->object, 'name', 'Charles Babbage');
        $this->assertSame('Charles Babbage', $this->object->name);

        Reflector::set($this->object, 'hobbies.0', 'cycling');
        $this->assertSame('cycling', $this->object->hobbies[0]);
        $this->assertSame(['cycling','outdoor'], $this->object->hobbies);

        Reflector::set($this->object, 'hobbies', ['game']);
        $this->assertSame('game', $this->object->hobbies[0]);
        $this->assertSame(['game'], $this->object->hobbies);

        Reflector::set($this->object, 'partner.name', 'Georgiana Whitmore');
        $this->assertSame('Georgiana Whitmore', $this->object->partner->name);

        Reflector::set($this->transparent, 'a.b', 'AB');
        $this->assertSame(Reflector::get($this->transparent, 'a.b'), 'AB');

        Reflector::set($this->transparent, 'a.c', 'AC');
        $this->assertSame(Reflector::get($this->transparent, 'a.c'), 'AC');

        Reflector::set($this->transparent, 'b', 'B');
        $this->assertSame(Reflector::get($this->transparent, 'b'), 'B');
    }

    /**
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Nested terminate key undefind_key does not exist.
     */
    public function test_set_undefindKeyArray()
    {
        Reflector::set($this->array, 'undefind_key', 'value');
    }

    /**
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Nested terminate key undefind_key does not exist.
     */
    public function test_set_nestedUndefindKeyArray()
    {
        Reflector::set($this->map, 'partner.undefind_key', 'value');
    }

    /**
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Nested terminate key undefind_key does not exist.
     */
    public function test_set_undefindKeyObject()
    {
        Reflector::set($this->object, 'undefind_key', 'value');
    }

    /**
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Nested terminate key undefind_key does not exist.
     */
    public function test_set_nestedUndefindKeyObject()
    {
        Reflector::set($this->object, 'partner.undefind_key', 'value');
    }

    public function test_typeOf()
    {
        $this->assertFalse(Reflector::typeOf(null, 'int'));
        $this->assertFalse(Reflector::typeOf(null, ReflectorTest_Mock::class));

        $this->assertTrue(Reflector::typeOf(1, 'int'));
        $this->assertFalse(Reflector::typeOf(1, 'float'));
        $this->assertFalse(Reflector::typeOf(1, 'bool'));
        $this->assertFalse(Reflector::typeOf(1, 'array'));
        $this->assertFalse(Reflector::typeOf(1, 'string'));
        $this->assertFalse(Reflector::typeOf(1, 'callable'));
        $this->assertFalse(Reflector::typeOf(1, ReflectorTest_Mock::class));

        $this->assertFalse(Reflector::typeOf(1.2, 'int'));
        $this->assertTrue(Reflector::typeOf(1.2, 'float'));
        $this->assertFalse(Reflector::typeOf(1.2, 'bool'));
        $this->assertFalse(Reflector::typeOf(1.2, 'array'));
        $this->assertFalse(Reflector::typeOf(1.2, 'string'));
        $this->assertFalse(Reflector::typeOf(1.2, 'callable'));
        $this->assertFalse(Reflector::typeOf(1.2, ReflectorTest_Mock::class));

        $this->assertFalse(Reflector::typeOf(true, 'int'));
        $this->assertFalse(Reflector::typeOf(true, 'float'));
        $this->assertTrue(Reflector::typeOf(true, 'bool'));
        $this->assertFalse(Reflector::typeOf(true, 'array'));
        $this->assertFalse(Reflector::typeOf(true, 'string'));
        $this->assertFalse(Reflector::typeOf(true, 'callable'));
        $this->assertFalse(Reflector::typeOf(true, ReflectorTest_Mock::class));

        $this->assertFalse(Reflector::typeOf([1, 2], 'int'));
        $this->assertFalse(Reflector::typeOf([1, 2], 'float'));
        $this->assertFalse(Reflector::typeOf([1, 2], 'bool'));
        $this->assertTrue(Reflector::typeOf([1, 2], 'array'));
        $this->assertFalse(Reflector::typeOf([1, 2], 'string'));
        $this->assertFalse(Reflector::typeOf([1, 2], 'callable'));
        $this->assertFalse(Reflector::typeOf([1, 2], ReflectorTest_Mock::class));

        $this->assertFalse(Reflector::typeOf('abc', 'int'));
        $this->assertFalse(Reflector::typeOf('abc', 'float'));
        $this->assertFalse(Reflector::typeOf('abc', 'bool'));
        $this->assertFalse(Reflector::typeOf('abc', 'array'));
        $this->assertTrue(Reflector::typeOf('abc', 'string'));
        $this->assertFalse(Reflector::typeOf('abc', 'callable'));
        $this->assertFalse(Reflector::typeOf('abc', ReflectorTest_Mock::class));

        $callable = function () {
            return 1;
        };
        $this->assertFalse(Reflector::typeOf($callable, 'int'));
        $this->assertFalse(Reflector::typeOf($callable, 'float'));
        $this->assertFalse(Reflector::typeOf($callable, 'bool'));
        $this->assertFalse(Reflector::typeOf($callable, 'array'));
        $this->assertFalse(Reflector::typeOf($callable, 'string'));
        $this->assertTrue(Reflector::typeOf($callable, 'callable'));
        $this->assertFalse(Reflector::typeOf($callable, ReflectorTest_Mock::class));

        $object = new ReflectorTest_Mock();
        $this->assertFalse(Reflector::typeOf($object, 'int'));
        $this->assertFalse(Reflector::typeOf($object, 'float'));
        $this->assertFalse(Reflector::typeOf($object, 'bool'));
        $this->assertFalse(Reflector::typeOf($object, 'array'));
        $this->assertFalse(Reflector::typeOf($object, 'string'));
        $this->assertFalse(Reflector::typeOf($object, 'callable'));
        $this->assertTrue(Reflector::typeOf($object, ReflectorTest_Mock::class));
        $this->assertFalse(Reflector::typeOf($object, \stdClass::class));
    }

    public function test_convert()
    {
        $this->assertNull(Reflector::convert(null, 'int'));
        $this->assertNull(Reflector::convert(null, ReflectorTest_Mock::class));

        $this->assertSame(1, Reflector::convert(1, 'int'));
        $this->assertSame(1.0, Reflector::convert(1, 'float'));
        $this->assertSame(true, Reflector::convert(1, 'bool'));
        $this->assertSame([1], Reflector::convert(1, 'array'));
        $this->assertSame('1', Reflector::convert(1, 'string'));
        $this->assertSame(null, Reflector::convert(1, 'callable'));
        $this->assertSame(null, Reflector::convert(1, ReflectorTest_Mock::class));
        $this->assertSame(ReflectorTest_Gender::MALE(), Reflector::convert(1, ReflectorTest_Gender::class));

        $this->assertSame(1, Reflector::convert('1', 'int'));
        $this->assertSame(1.0, Reflector::convert('1', 'float'));
        $this->assertSame(true, Reflector::convert('1', 'bool'));
        $this->assertSame(['1'], Reflector::convert('1', 'array'));
        $this->assertSame('1', Reflector::convert('1', 'string'));
        $this->assertSame(null, Reflector::convert('1', 'callable'));
        $this->assertSame(null, Reflector::convert('1', ReflectorTest_Mock::class));
        $this->assertSame(ReflectorTest_Gender::MALE(), Reflector::convert('1', ReflectorTest_Gender::class));
    }

    public function test_convert_array()
    {
        $type = 'array';
        $this->assertNull(Reflector::convert(null, $type));

        $this->assertSame([], Reflector::convert([], $type));
        $this->assertSame([1, 2], Reflector::convert([1, 2], $type));
        $this->assertSame(['a' => 'A'], Reflector::convert(['a' => 'A'], $type));

        $this->assertSame([''], Reflector::convert('', $type));
        $this->assertSame(['a'], Reflector::convert('a', $type));
        $this->assertSame(['a','b','c'], Reflector::convert('a,b,c', $type));

        $to_array = new ReflectorTest_ToArray([1, 2, 'a' => 'A']);
        $this->assertSame([1, 2, 'a' => 'A'], Reflector::convert($to_array, $type));

        $travers = new \ArrayObject([1, 2, 'a' => 'A']);
        $this->assertSame([1, 2, 'a' => 'A'], Reflector::convert($travers, $type));

        $jsonValue = ReflectorTest_Gender::MALE();
        $this->assertSame([$jsonValue], Reflector::convert($jsonValue, $type));
        $jsonValue = new ReflectorTest_Json('abc');
        $this->assertSame([$jsonValue], Reflector::convert($jsonValue, $type));

        $jsonArray = new ReflectorTest_Json([1, 2, 'a' => 'A']);
        $this->assertSame([1, 2, 'a' => 'A'], Reflector::convert($jsonArray, $type));

        $object = new ReflectorTest_Mock();
        $this->assertSame(['value' => 'default'], Reflector::convert($object, $type));

        $this->assertSame([1], Reflector::convert(1, $type));
        $this->assertSame([1.2], Reflector::convert(1.2, $type));
        $this->assertSame([true], Reflector::convert(true, $type));

        $resource = fopen('vfs://root/dummy.txt', 'r');
        $this->assertSame([$resource], Reflector::convert($resource, $type));
        fclose($resource);
    }

    public function test_convert_string()
    {
        $type = 'string';
        $this->assertNull(Reflector::convert(null, $type));

        $this->assertSame('', Reflector::convert('', $type));
        $this->assertSame('a', Reflector::convert('a', $type));
        $this->assertSame('a,b,c', Reflector::convert('a,b,c', $type));

        $resource = fopen('vfs://root/dummy.txt', 'r');
        $this->assertSame(null, Reflector::convert($resource, $type));
        fclose($resource);

        $this->assertSame('1', Reflector::convert(1, $type));
        $this->assertSame('1.2', Reflector::convert(1.2, $type));
        $this->assertSame('1200', Reflector::convert(1.2e3, $type));
        $this->assertSame('1', Reflector::convert(true, $type));
        $this->assertSame('', Reflector::convert(false, $type));
        $this->assertSame('abc', Reflector::convert('abc', $type));

        $jsonValue = ReflectorTest_Gender::MALE();
        $this->assertSame('1', Reflector::convert($jsonValue, $type));
        $jsonValue = new ReflectorTest_Json('abc');
        $this->assertSame('abc', Reflector::convert($jsonValue, $type));

        $jsonArray = new ReflectorTest_Json([1, 2]);
        $this->assertSame('value: 1,2', Reflector::convert($jsonArray, $type));
        $toString  = new ReflectorTest_Mock();
        $this->assertSame('default', Reflector::convert($toString, $type));

        $unconvertable = [1, 2];
        $this->assertSame(null, Reflector::convert($unconvertable, $type));
        $unconvertable = new ReflectorTest_ToArray([1, 2]);
        $this->assertSame(null, Reflector::convert($unconvertable, $type));
    }

    public function test_convert_int()
    {
        $type = 'int';
        $this->assertNull(Reflector::convert(null, $type));

        $this->assertSame(1, Reflector::convert(1, $type));
        $this->assertSame(1, Reflector::convert(1.2, $type));
        $this->assertSame(2, Reflector::convert(2.9, $type));
        $this->assertSame(1, Reflector::convert('1', $type));
        $this->assertSame(1, Reflector::convert('1.2', $type));
        $this->assertSame(2, Reflector::convert('2.9', $type));
        $this->assertSame(0, Reflector::convert('a', $type));

        $this->assertSame(null, Reflector::convert([1, 2], $type));
        
        $resource = fopen('vfs://root/dummy.txt', 'r');
        $this->assertSame(null, Reflector::convert($resource, $type));
        fclose($resource);

        $object = new ReflectorTest_Mock();
        $this->assertSame(null, Reflector::convert($object, $type));
    }
}

class ReflectorTest_Mock
{
    public $value = null;
    public function __construct($value = 'default')
    {
        $this->value = $value;
    }
    public static function getInstance()
    {
        return new static('via getInstance()');
    }
    public static function build($value)
    {
        return new static($value.' via build()');
    }
    public function __toString()
    {
        return (string)$this->value;
    }
}

class ReflectorTest_Gender extends Enum
{
    const MALE   = [1, '男性'];
    const FEMALE = [2, '女性'];
}

class ReflectorTest_ToArray
{
    private $array;
    public function __construct(array $array)
    {
        $this->array = $array;
    }
    public function toArray() : array
    {
        return $this->array;
    }
}
class ReflectorTest_Json implements \JsonSerializable
{
    private $value;
    public function __construct($value)
    {
        $this->value = $value;
    }
    public function jsonSerialize()
    {
        return $this->value;
    }
    public function __toString()
    {
        return "value: ".join(',', (array)$this->value);
    }
}
