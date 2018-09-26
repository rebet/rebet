<?php
namespace Rebet\Tests\Common;

use Rebet\Tests\RebetTestCase;
use Rebet\Common\Util;
use Rebet\Common\TransparentlyDotAccessible;

class UtilTest extends RebetTestCase
{
    const TEST_VALUE = "UtilTest::TEST_VALUE";

    private $array       = null;
    private $map         = null;
    private $object      = null;
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
            'a' => new class() implements TransparentlyDotAccessible {
                public function get()
                {
                    return [
                        'b' => 'ab',
                        'c' => new class() implements TransparentlyDotAccessible {
                            public function get()
                            {
                                return 'ac';
                            }
                        },
                    ];
                }
            },
            'b' => new class() implements TransparentlyDotAccessible {
                public function get()
                {
                    return new class() implements TransparentlyDotAccessible {
                        public function get()
                        {
                            return 'b';
                        }
                    };
                }
            }
        ];
    }

    public function test_when()
    {
        $this->assertSame('no', Util::when(null, 'yes', 'no'));
        $this->assertSame('no', Util::when(0, 'yes', 'no'));
        $this->assertSame('yes', Util::when(1, 'yes', 'no'));
        $this->assertSame('yes', Util::when(1 === 1, 'yes', 'no'));
        $this->assertSame('no', Util::when(1 === 2, 'yes', 'no'));
    }

    public function test_coalesce()
    {
        $this->assertSame(3, Util::coalesce(null, [], '', 0, 3, 'a'));
        $this->assertSame('a', Util::coalesce('a', null, [], '', 0, 3));
    }

    public function test_instantiate()
    {
        $this->assertNull(Util::instantiate(null));
        $this->assertNull(Util::instantiate(''));
        $this->assertNull(Util::instantiate([]));

        $this->assertSame('default', Util::instantiate(UtilTest_Mock::class)->value);
        $this->assertSame('via getInstance()', Util::instantiate(UtilTest_Mock::class.'::getInstance')->value);
        $this->assertSame('callable', Util::instantiate(function () {
            return 'callable';
        }));
        $this->assertSame('arg', Util::instantiate([UtilTest_Mock::class, 'arg'])->value);
        $this->assertSame('arg via build()', Util::instantiate([UtilTest_Mock::class.'::build', 'arg'])->value);
        $this->assertSame('arg via callable', Util::instantiate([function ($v) {
            return $v.' via callable';
        }, 'arg']));
        $this->assertSame(123, Util::instantiate(123));
        $this->assertSame('instantiated', Util::instantiate(new UtilTest_Mock('instantiated'))->value);
    }

    public function test_get()
    {
        $this->assertSame($this->array, Util::get($this->array, null));
        $this->assertSame($this->array, Util::get($this->array, ''));
        $this->assertNull(Util::get($this->array, 'invalid_key'));
        $this->assertSame('default', Util::get($this->array, 'invalid_key', 'default'));
        $this->assertSame('a', Util::get($this->array, 0, 'default'));
        $this->assertSame('default', Util::get($this->array, 3, 'default'));
        $this->assertSame('default', Util::get($this->array, 4, 'default'));

        $this->assertSame($this->map, Util::get($this->map, null));
        $this->assertSame($this->map, Util::get($this->map, ''));
        $this->assertSame('default', Util::get($this->map, 'invalid_key', 'default'));
        $this->assertSame('John Smith', Util::get($this->map, 'name', 'default'));
        $this->assertSame(['game', 'outdoor'], Util::get($this->map, 'hobbies', []));
        $this->assertSame('game', Util::get($this->map, 'hobbies.0', 'default'));
        $this->assertSame('default', Util::get($this->map, 'hobbies.3', 'default'));
        $this->assertSame('default', Util::get($this->map, 'hobbies.invalid_key', 'default'));
        $this->assertSame('Jane Smith', Util::get($this->map, 'partner.name', 'default'));
        $this->assertSame('female', Util::get($this->map, 'partner.gender', 'default'));
        $this->assertSame('default', Util::get($this->map, 'partner.invalid_key', 'default'));
        $this->assertSame('default', Util::get($this->map, 'partner.gender.invalid_key', 'default'));
        $this->assertSame([], Util::get($this->map, 'children', []));

        $this->assertSame($this->object, Util::get($this->object, null));
        $this->assertSame($this->object, Util::get($this->object, ''));
        $this->assertSame('default', Util::get($this->object, 'invalid_key', 'default'));
        $this->assertSame('John Smith', Util::get($this->object, 'name', 'default'));
        $this->assertSame(['game', 'outdoor'], Util::get($this->object, 'hobbies', []));
        $this->assertSame('game', Util::get($this->object, 'hobbies.0', 'default'));
        $this->assertSame('default', Util::get($this->object, 'hobbies.3', 'default'));
        $this->assertSame('default', Util::get($this->object, 'hobbies.invalid_key', 'default'));
        $this->assertSame('Jane Smith', Util::get($this->object, 'partner.name', 'default'));
        $this->assertSame('female', Util::get($this->object, 'partner.gender', 'default'));
        $this->assertSame('default', Util::get($this->object, 'partner.invalid_key', 'default'));
        $this->assertSame('default', Util::get($this->object, 'partner.gender.invalid_key', 'default'));
        $this->assertSame([], Util::get($this->object, 'children', []));

        $this->assertSame(
            [
                'b' => 'ab',
                'c' => 'ac',
            ],
            Util::get($this->transparent->a, null)
        );
        $this->assertSame('b', Util::get($this->transparent->b, null));
        $this->assertSame('b', Util::get($this->transparent->b, ''));
        $this->assertSame('ab', Util::get($this->transparent, 'a.b'));
        $this->assertSame('ac', Util::get($this->transparent, 'a.c'));
        $this->assertSame('b', Util::get($this->transparent, 'b'));
    }

    public function test_has()
    {
        $this->assertFalse(Util::has(null, 'invalid_key'));
        $this->assertFalse(Util::has('string', 'invalid_key'));

        $this->assertFalse(Util::has($this->array, 'invalid_key'));
        $this->assertTrue(Util::has($this->array, 0));
        $this->assertTrue(Util::has($this->array, 3));
        $this->assertFalse(Util::has($this->array, 4));

        $this->assertFalse(Util::has($this->map, 'invalid_key'));
        $this->assertTrue(Util::has($this->map, 'name'));
        $this->assertTrue(Util::has($this->map, 'hobbies'));
        $this->assertTrue(Util::has($this->map, 'hobbies.0'));
        $this->assertFalse(Util::has($this->map, 'hobbies.3'));
        $this->assertFalse(Util::has($this->map, 'hobbies.invalid_key'));
        $this->assertTrue(Util::has($this->map, 'partner.name'));
        $this->assertTrue(Util::has($this->map, 'partner.gender'));
        $this->assertFalse(Util::has($this->map, 'partner.invalid_key'));
        $this->assertFalse(Util::has($this->map, 'partner.gender.invalid_key'));
        $this->assertTrue(Util::has($this->map, 'children'));
        $this->assertFalse(Util::has($this->map, 'children.0'));

        $this->assertFalse(Util::has($this->object, 'invalid_key'));
        $this->assertTrue(Util::has($this->object, 'name'));
        $this->assertTrue(Util::has($this->object, 'hobbies'));
        $this->assertTrue(Util::has($this->object, 'hobbies.0'));
        $this->assertFalse(Util::has($this->object, 'hobbies.3'));
        $this->assertFalse(Util::has($this->object, 'hobbies.invalid_key'));
        $this->assertTrue(Util::has($this->object, 'partner.name'));
        $this->assertTrue(Util::has($this->object, 'partner.gender'));
        $this->assertFalse(Util::has($this->object, 'partner.invalid_key'));
        $this->assertFalse(Util::has($this->object, 'partner.gender.invalid_key'));
        $this->assertTrue(Util::has($this->object, 'children'));
        $this->assertFalse(Util::has($this->object, 'children.0'));

        $this->assertTrue(Util::has($this->transparent, 'a'));
        $this->assertTrue(Util::has($this->transparent, 'a.b'));
        $this->assertFalse(Util::has($this->transparent, 'a.b.c'));
        $this->assertTrue(Util::has($this->transparent, 'a.c'));
        $this->assertFalse(Util::has($this->transparent, 'a.c.d'));
        $this->assertFalse(Util::has($this->transparent, 'a.d'));
        $this->assertTrue(Util::has($this->transparent, 'b'));
        $this->assertFalse(Util::has($this->transparent, 'b.c'));
    }

    public function test_set()
    {
        Util::set($this->array, 0, 'A');
        $this->assertSame('A', $this->array[0]);

        Util::set($this->array, '1', 'B');
        $this->assertSame('B', $this->array[1]);


        Util::set($this->map, 'name', 'Charles Babbage');
        $this->assertSame('Charles Babbage', $this->map['name']);

        Util::set($this->map, 'hobbies.0', 'cycling');
        $this->assertSame('cycling', $this->map['hobbies'][0]);
        $this->assertSame(['cycling','outdoor'], $this->map['hobbies']);

        Util::set($this->map, 'hobbies', ['game']);
        $this->assertSame('game', $this->map['hobbies'][0]);
        $this->assertSame(['game'], $this->map['hobbies']);

        Util::set($this->map, 'partner.name', 'Georgiana Whitmore');
        $this->assertSame('Georgiana Whitmore', $this->map['partner']['name']);


        Util::set($this->object, 'name', 'Charles Babbage');
        $this->assertSame('Charles Babbage', $this->object->name);

        Util::set($this->object, 'hobbies.0', 'cycling');
        $this->assertSame('cycling', $this->object->hobbies[0]);
        $this->assertSame(['cycling','outdoor'], $this->object->hobbies);

        Util::set($this->object, 'hobbies', ['game']);
        $this->assertSame('game', $this->object->hobbies[0]);
        $this->assertSame(['game'], $this->object->hobbies);

        Util::set($this->object, 'partner.name', 'Georgiana Whitmore');
        $this->assertSame('Georgiana Whitmore', $this->object->partner->name);

        Util::set($this->transparent, 'a.b', 'AB');
        $this->assertSame(Util::get($this->transparent, 'a.b'), 'AB');

        Util::set($this->transparent, 'a.c', 'AC');
        $this->assertSame(Util::get($this->transparent, 'a.c'), 'AC');

        Util::set($this->transparent, 'b', 'B');
        $this->assertSame(Util::get($this->transparent, 'b'), 'B');
    }

    /**
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Nested terminate key undefind_key does not exist.
     */
    public function test_set_undefindKeyArray()
    {
        Util::set($this->array, 'undefind_key', 'value');
    }

    /**
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Nested terminate key undefind_key does not exist.
     */
    public function test_set_nestedUndefindKeyArray()
    {
        Util::set($this->map, 'partner.undefind_key', 'value');
    }

    /**
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Nested terminate key undefind_key does not exist.
     */
    public function test_set_undefindKeyObject()
    {
        Util::set($this->object, 'undefind_key', 'value');
    }

    /**
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Nested terminate key undefind_key does not exist.
     */
    public function test_set_nestedUndefindKeyObject()
    {
        Util::set($this->object, 'partner.undefind_key', 'value');
    }

    public function test_isBlank()
    {
        $this->assertTrue(Util::isBlank(null));
        $this->assertFalse(Util::isBlank(false));
        $this->assertFalse(Util::isBlank('false'));
        $this->assertFalse(Util::isBlank(0));
        $this->assertFalse(Util::isBlank('0'));
        $this->assertTrue(Util::isBlank(''));
        $this->assertTrue(Util::isBlank([]));
        $this->assertFalse(Util::isBlank([null]));
        $this->assertFalse(Util::isBlank([1]));
        $this->assertFalse(Util::isBlank('abc'));
    }

    public function test_bvl()
    {
        $this->assertSame('default', Util::bvl(null, 'default'));
        $this->assertSame(false, Util::bvl(false, 'default'));
        $this->assertSame('false', Util::bvl('false', 'default'));
        $this->assertSame(0, Util::bvl(0, 'default'));
        $this->assertSame('0', Util::bvl('0', 'default'));
        $this->assertSame('default', Util::bvl('', 'default'));
        $this->assertSame('default', Util::bvl([], 'default'));
        $this->assertSame([null], Util::bvl([null], 'default'));
        $this->assertSame('abc', Util::bvl('abc', 'default'));
    }

    public function test_isEmpty()
    {
        $this->assertTrue(Util::isEmpty(null));
        $this->assertFalse(Util::isEmpty(false));
        $this->assertFalse(Util::isEmpty('false'));
        $this->assertTrue(Util::isEmpty(0));
        $this->assertFalse(Util::isEmpty('0'));
        $this->assertTrue(Util::isEmpty(''));
        $this->assertTrue(Util::isEmpty([]));
        $this->assertFalse(Util::isEmpty([null]));
        $this->assertFalse(Util::isEmpty([1]));
        $this->assertFalse(Util::isEmpty('abc'));
    }

    public function test_evl()
    {
        $this->assertSame('default', Util::evl(null, 'default'));
        $this->assertSame(false, Util::evl(false, 'default'));
        $this->assertSame('false', Util::evl('false', 'default'));
        $this->assertSame('default', Util::evl(0, 'default'));
        $this->assertSame('0', Util::evl('0', 'default'));
        $this->assertSame('default', Util::evl('', 'default'));
        $this->assertSame('default', Util::evl([], 'default'));
        $this->assertSame([null], Util::evl([null], 'default'));
        $this->assertSame('abc', Util::evl('abc', 'default'));
    }

    public function test_heredocImplanter()
    {
        $_ = Util::heredocImplanter();
        $expected = <<<EOS
START
UtilTest::TEST_VALUE
END
EOS;
        $actual = <<<EOS
START
{$_(UtilTest::TEST_VALUE)}
END
EOS;

        $this->assertSame($expected, $actual);
    }

    public function test_intval()
    {
        $this->assertNull(Util::intval(null));
        $this->assertNull(Util::intval(''));
        $this->assertSame(0, Util::intval('abc'));
        $this->assertSame(123, Util::intval('123'));
        $this->assertSame(123, Util::intval('123abc567'));
        $this->assertSame(123, Util::intval(123));
        $this->assertSame(123, Util::intval(123.0));

        $this->assertSame(011, Util::intval('11', 8));
        $this->assertSame(0xF, Util::intval('F', 16));
    }

    public function test_floatval()
    {
        $this->assertNull(Util::floatval(null));
        $this->assertNull(Util::floatval(''));
        $this->assertSame(0.0, Util::floatval('abc'));
        $this->assertSame(123.0, Util::floatval('123'));
        $this->assertSame(123.45, Util::floatval('123.45'));
        $this->assertSame(123.0, Util::floatval('123abc567'));
        $this->assertSame(123.45, Util::floatval('123.45abc567'));
        $this->assertSame(123.0, Util::floatval(123));
        $this->assertSame(123.0, Util::floatval(123.0));
    }
}

class UtilTest_Mock
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
