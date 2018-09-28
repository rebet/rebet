<?php
namespace Rebet\Tests\Common;

use Rebet\Tests\RebetTestCase;
use Rebet\Common\Utils;
use Rebet\Common\DotAccessDelegator;

class UtilsTest extends RebetTestCase
{
    const TEST_VALUE = "UtilsTest::TEST_VALUE";

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

    public function test_when()
    {
        $this->assertSame('no', Utils::when(null, 'yes', 'no'));
        $this->assertSame('no', Utils::when(0, 'yes', 'no'));
        $this->assertSame('yes', Utils::when(1, 'yes', 'no'));
        $this->assertSame('yes', Utils::when(1 === 1, 'yes', 'no'));
        $this->assertSame('no', Utils::when(1 === 2, 'yes', 'no'));
    }

    public function test_coalesce()
    {
        $this->assertSame(3, Utils::coalesce(null, [], '', 0, 3, 'a'));
        $this->assertSame('a', Utils::coalesce('a', null, [], '', 0, 3));
    }

    public function test_instantiate()
    {
        $this->assertNull(Utils::instantiate(null));
        $this->assertNull(Utils::instantiate(''));
        $this->assertNull(Utils::instantiate([]));

        $this->assertSame('default', Utils::instantiate(UtilsTest_Mock::class)->value);
        $this->assertSame('via getInstance()', Utils::instantiate(UtilsTest_Mock::class.'::getInstance')->value);
        $this->assertSame('callable', Utils::instantiate(function () {
            return 'callable';
        }));
        $this->assertSame('arg', Utils::instantiate([UtilsTest_Mock::class, 'arg'])->value);
        $this->assertSame('arg via build()', Utils::instantiate([UtilsTest_Mock::class.'::build', 'arg'])->value);
        $this->assertSame('arg via callable', Utils::instantiate([function ($v) {
            return $v.' via callable';
        }, 'arg']));
        $this->assertSame(123, Utils::instantiate(123));
        $this->assertSame('instantiated', Utils::instantiate(new UtilsTest_Mock('instantiated'))->value);
    }

    public function test_get()
    {
        $this->assertSame($this->array, Utils::get($this->array, null));
        $this->assertSame($this->array, Utils::get($this->array, ''));
        $this->assertNull(Utils::get($this->array, 'invalid_key'));
        $this->assertSame('default', Utils::get($this->array, 'invalid_key', 'default'));
        $this->assertSame('a', Utils::get($this->array, 0, 'default'));
        $this->assertSame('default', Utils::get($this->array, 3, 'default'));
        $this->assertSame('default', Utils::get($this->array, 4, 'default'));

        $this->assertSame($this->map, Utils::get($this->map, null));
        $this->assertSame($this->map, Utils::get($this->map, ''));
        $this->assertSame('default', Utils::get($this->map, 'invalid_key', 'default'));
        $this->assertSame('John Smith', Utils::get($this->map, 'name', 'default'));
        $this->assertSame(['game', 'outdoor'], Utils::get($this->map, 'hobbies', []));
        $this->assertSame('game', Utils::get($this->map, 'hobbies.0', 'default'));
        $this->assertSame('default', Utils::get($this->map, 'hobbies.3', 'default'));
        $this->assertSame('default', Utils::get($this->map, 'hobbies.invalid_key', 'default'));
        $this->assertSame('Jane Smith', Utils::get($this->map, 'partner.name', 'default'));
        $this->assertSame('female', Utils::get($this->map, 'partner.gender', 'default'));
        $this->assertSame('default', Utils::get($this->map, 'partner.invalid_key', 'default'));
        $this->assertSame('default', Utils::get($this->map, 'partner.gender.invalid_key', 'default'));
        $this->assertSame([], Utils::get($this->map, 'children', []));

        $this->assertSame($this->object, Utils::get($this->object, null));
        $this->assertSame($this->object, Utils::get($this->object, ''));
        $this->assertSame('default', Utils::get($this->object, 'invalid_key', 'default'));
        $this->assertSame('John Smith', Utils::get($this->object, 'name', 'default'));
        $this->assertSame(['game', 'outdoor'], Utils::get($this->object, 'hobbies', []));
        $this->assertSame('game', Utils::get($this->object, 'hobbies.0', 'default'));
        $this->assertSame('default', Utils::get($this->object, 'hobbies.3', 'default'));
        $this->assertSame('default', Utils::get($this->object, 'hobbies.invalid_key', 'default'));
        $this->assertSame('Jane Smith', Utils::get($this->object, 'partner.name', 'default'));
        $this->assertSame('female', Utils::get($this->object, 'partner.gender', 'default'));
        $this->assertSame('default', Utils::get($this->object, 'partner.invalid_key', 'default'));
        $this->assertSame('default', Utils::get($this->object, 'partner.gender.invalid_key', 'default'));
        $this->assertSame([], Utils::get($this->object, 'children', []));

        $this->assertSame(
            [
                'b' => 'ab',
                'c' => 'ac',
            ],
            Utils::get($this->transparent->a, null)
        );
        $this->assertSame('b', Utils::get($this->transparent->b, null));
        $this->assertSame('b', Utils::get($this->transparent->b, ''));
        $this->assertSame('ab', Utils::get($this->transparent, 'a.b'));
        $this->assertSame('ac', Utils::get($this->transparent, 'a.c'));
        $this->assertSame('b', Utils::get($this->transparent, 'b'));
    }

    public function test_has()
    {
        $this->assertFalse(Utils::has(null, 'invalid_key'));
        $this->assertFalse(Utils::has('string', 'invalid_key'));

        $this->assertFalse(Utils::has($this->array, 'invalid_key'));
        $this->assertTrue(Utils::has($this->array, 0));
        $this->assertTrue(Utils::has($this->array, 3));
        $this->assertFalse(Utils::has($this->array, 4));

        $this->assertFalse(Utils::has($this->map, 'invalid_key'));
        $this->assertTrue(Utils::has($this->map, 'name'));
        $this->assertTrue(Utils::has($this->map, 'hobbies'));
        $this->assertTrue(Utils::has($this->map, 'hobbies.0'));
        $this->assertFalse(Utils::has($this->map, 'hobbies.3'));
        $this->assertFalse(Utils::has($this->map, 'hobbies.invalid_key'));
        $this->assertTrue(Utils::has($this->map, 'partner.name'));
        $this->assertTrue(Utils::has($this->map, 'partner.gender'));
        $this->assertFalse(Utils::has($this->map, 'partner.invalid_key'));
        $this->assertFalse(Utils::has($this->map, 'partner.gender.invalid_key'));
        $this->assertTrue(Utils::has($this->map, 'children'));
        $this->assertFalse(Utils::has($this->map, 'children.0'));

        $this->assertFalse(Utils::has($this->object, 'invalid_key'));
        $this->assertTrue(Utils::has($this->object, 'name'));
        $this->assertTrue(Utils::has($this->object, 'hobbies'));
        $this->assertTrue(Utils::has($this->object, 'hobbies.0'));
        $this->assertFalse(Utils::has($this->object, 'hobbies.3'));
        $this->assertFalse(Utils::has($this->object, 'hobbies.invalid_key'));
        $this->assertTrue(Utils::has($this->object, 'partner.name'));
        $this->assertTrue(Utils::has($this->object, 'partner.gender'));
        $this->assertFalse(Utils::has($this->object, 'partner.invalid_key'));
        $this->assertFalse(Utils::has($this->object, 'partner.gender.invalid_key'));
        $this->assertTrue(Utils::has($this->object, 'children'));
        $this->assertFalse(Utils::has($this->object, 'children.0'));

        $this->assertTrue(Utils::has($this->transparent, 'a'));
        $this->assertTrue(Utils::has($this->transparent, 'a.b'));
        $this->assertFalse(Utils::has($this->transparent, 'a.b.c'));
        $this->assertTrue(Utils::has($this->transparent, 'a.c'));
        $this->assertFalse(Utils::has($this->transparent, 'a.c.d'));
        $this->assertFalse(Utils::has($this->transparent, 'a.d'));
        $this->assertTrue(Utils::has($this->transparent, 'b'));
        $this->assertFalse(Utils::has($this->transparent, 'b.c'));
    }

    public function test_set()
    {
        Utils::set($this->array, 0, 'A');
        $this->assertSame('A', $this->array[0]);

        Utils::set($this->array, '1', 'B');
        $this->assertSame('B', $this->array[1]);


        Utils::set($this->map, 'name', 'Charles Babbage');
        $this->assertSame('Charles Babbage', $this->map['name']);

        Utils::set($this->map, 'hobbies.0', 'cycling');
        $this->assertSame('cycling', $this->map['hobbies'][0]);
        $this->assertSame(['cycling','outdoor'], $this->map['hobbies']);

        Utils::set($this->map, 'hobbies', ['game']);
        $this->assertSame('game', $this->map['hobbies'][0]);
        $this->assertSame(['game'], $this->map['hobbies']);

        Utils::set($this->map, 'partner.name', 'Georgiana Whitmore');
        $this->assertSame('Georgiana Whitmore', $this->map['partner']['name']);


        Utils::set($this->object, 'name', 'Charles Babbage');
        $this->assertSame('Charles Babbage', $this->object->name);

        Utils::set($this->object, 'hobbies.0', 'cycling');
        $this->assertSame('cycling', $this->object->hobbies[0]);
        $this->assertSame(['cycling','outdoor'], $this->object->hobbies);

        Utils::set($this->object, 'hobbies', ['game']);
        $this->assertSame('game', $this->object->hobbies[0]);
        $this->assertSame(['game'], $this->object->hobbies);

        Utils::set($this->object, 'partner.name', 'Georgiana Whitmore');
        $this->assertSame('Georgiana Whitmore', $this->object->partner->name);

        Utils::set($this->transparent, 'a.b', 'AB');
        $this->assertSame(Utils::get($this->transparent, 'a.b'), 'AB');

        Utils::set($this->transparent, 'a.c', 'AC');
        $this->assertSame(Utils::get($this->transparent, 'a.c'), 'AC');

        Utils::set($this->transparent, 'b', 'B');
        $this->assertSame(Utils::get($this->transparent, 'b'), 'B');
    }

    /**
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Nested terminate key undefind_key does not exist.
     */
    public function test_set_undefindKeyArray()
    {
        Utils::set($this->array, 'undefind_key', 'value');
    }

    /**
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Nested terminate key undefind_key does not exist.
     */
    public function test_set_nestedUndefindKeyArray()
    {
        Utils::set($this->map, 'partner.undefind_key', 'value');
    }

    /**
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Nested terminate key undefind_key does not exist.
     */
    public function test_set_undefindKeyObject()
    {
        Utils::set($this->object, 'undefind_key', 'value');
    }

    /**
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Nested terminate key undefind_key does not exist.
     */
    public function test_set_nestedUndefindKeyObject()
    {
        Utils::set($this->object, 'partner.undefind_key', 'value');
    }

    public function test_isBlank()
    {
        $this->assertTrue(Utils::isBlank(null));
        $this->assertFalse(Utils::isBlank(false));
        $this->assertFalse(Utils::isBlank('false'));
        $this->assertFalse(Utils::isBlank(0));
        $this->assertFalse(Utils::isBlank('0'));
        $this->assertTrue(Utils::isBlank(''));
        $this->assertTrue(Utils::isBlank([]));
        $this->assertFalse(Utils::isBlank([null]));
        $this->assertFalse(Utils::isBlank([1]));
        $this->assertFalse(Utils::isBlank('abc'));
    }

    public function test_bvl()
    {
        $this->assertSame('default', Utils::bvl(null, 'default'));
        $this->assertSame(false, Utils::bvl(false, 'default'));
        $this->assertSame('false', Utils::bvl('false', 'default'));
        $this->assertSame(0, Utils::bvl(0, 'default'));
        $this->assertSame('0', Utils::bvl('0', 'default'));
        $this->assertSame('default', Utils::bvl('', 'default'));
        $this->assertSame('default', Utils::bvl([], 'default'));
        $this->assertSame([null], Utils::bvl([null], 'default'));
        $this->assertSame('abc', Utils::bvl('abc', 'default'));
    }

    public function test_isEmpty()
    {
        $this->assertTrue(Utils::isEmpty(null));
        $this->assertFalse(Utils::isEmpty(false));
        $this->assertFalse(Utils::isEmpty('false'));
        $this->assertTrue(Utils::isEmpty(0));
        $this->assertFalse(Utils::isEmpty('0'));
        $this->assertTrue(Utils::isEmpty(''));
        $this->assertTrue(Utils::isEmpty([]));
        $this->assertFalse(Utils::isEmpty([null]));
        $this->assertFalse(Utils::isEmpty([1]));
        $this->assertFalse(Utils::isEmpty('abc'));
    }

    public function test_evl()
    {
        $this->assertSame('default', Utils::evl(null, 'default'));
        $this->assertSame(false, Utils::evl(false, 'default'));
        $this->assertSame('false', Utils::evl('false', 'default'));
        $this->assertSame('default', Utils::evl(0, 'default'));
        $this->assertSame('0', Utils::evl('0', 'default'));
        $this->assertSame('default', Utils::evl('', 'default'));
        $this->assertSame('default', Utils::evl([], 'default'));
        $this->assertSame([null], Utils::evl([null], 'default'));
        $this->assertSame('abc', Utils::evl('abc', 'default'));
    }

    public function test_heredocImplanter()
    {
        $_ = Utils::heredocImplanter();
        $expected = <<<EOS
START
UtilsTest::TEST_VALUE
END
EOS;
        $actual = <<<EOS
START
{$_(UtilsTest::TEST_VALUE)}
END
EOS;

        $this->assertSame($expected, $actual);
    }

    public function test_intval()
    {
        $this->assertNull(Utils::intval(null));
        $this->assertNull(Utils::intval(''));
        $this->assertSame(0, Utils::intval('abc'));
        $this->assertSame(123, Utils::intval('123'));
        $this->assertSame(123, Utils::intval('123abc567'));
        $this->assertSame(123, Utils::intval(123));
        $this->assertSame(123, Utils::intval(123.0));

        $this->assertSame(011, Utils::intval('11', 8));
        $this->assertSame(0xF, Utils::intval('F', 16));
    }

    public function test_floatval()
    {
        $this->assertNull(Utils::floatval(null));
        $this->assertNull(Utils::floatval(''));
        $this->assertSame(0.0, Utils::floatval('abc'));
        $this->assertSame(123.0, Utils::floatval('123'));
        $this->assertSame(123.45, Utils::floatval('123.45'));
        $this->assertSame(123.0, Utils::floatval('123abc567'));
        $this->assertSame(123.45, Utils::floatval('123.45abc567'));
        $this->assertSame(123.0, Utils::floatval(123));
        $this->assertSame(123.0, Utils::floatval(123.0));
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
