<?php
namespace Rebet\Tests\Common;

use Rebet\Tests\RebetTestCase;
use Rebet\Common\DotAccessDelegator;
use Rebet\Common\Reflector;
use Rebet\Common\Utils;

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
    }

    public function test_instantiate()
    {
        $this->assertNull(Reflector::instantiate(null));
        $this->assertNull(Reflector::instantiate(''));
        $this->assertNull(Reflector::instantiate([]));

        $this->assertSame('default', Reflector::instantiate(UtilsTest_Mock::class)->value);
        $this->assertSame('via getInstance()', Reflector::instantiate(UtilsTest_Mock::class. '@getInstance')->value);
        $this->assertSame('callable', Reflector::instantiate(function () {
            return 'callable';
        }));
        $this->assertSame('arg', Reflector::instantiate([UtilsTest_Mock::class, 'arg'])->value);
        $this->assertSame('arg via build()', Reflector::instantiate([UtilsTest_Mock::class. '@build', 'arg'])->value);
        $this->assertSame('arg via callable', Reflector::instantiate([function ($v) {
            return $v.' via callable';
        }, 'arg']));
        $this->assertSame(123, Reflector::instantiate(123));
        $this->assertSame('instantiated', Reflector::instantiate(new UtilsTest_Mock('instantiated'))->value);
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
}

class UtilsTest_Mock
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
}
