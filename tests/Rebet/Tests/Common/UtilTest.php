<?php
namespace Rebet\Tests\Common;

use Rebet\Tests\RebetTestCase;
use Rebet\Common\Util;

class UtilTest extends RebetTestCase {
    const TEST_VALUE = "UtilTest::TEST_VALUE";

    private $array  = null;
    private $map    = null;
    private $object = null;

    public function setUp() {
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
    }

    public function test_when() {
        $this->assertSame(Util::when(null, 'yes', 'no'), 'no');
        $this->assertSame(Util::when(0, 'yes', 'no'), 'no');
        $this->assertSame(Util::when(1, 'yes', 'no'), 'yes');
        $this->assertSame(Util::when(1 === 1, 'yes', 'no'), 'yes');
        $this->assertSame(Util::when(1 === 2, 'yes', 'no'), 'no');
    }

    public function test_coalesce() {
        $this->assertSame(Util::coalesce(null, [], '', 0, 3, 'a'), 3);
        $this->assertSame(Util::coalesce('a', null, [], '', 0, 3), 'a');
    }

    public function test_get() {
        $this->assertNull(Util::get($this->array, 'invalid_key'));
        $this->assertSame(Util::get($this->array, 'invalid_key', 'default'), 'default');
        $this->assertSame(Util::get($this->array, 0, 'default'), 'a');
        $this->assertSame(Util::get($this->array, 3, 'default'), 'default');
        $this->assertSame(Util::get($this->array, 4, 'default'), 'default');

        $this->assertSame(Util::get($this->map, 'invalid_key', 'default'), 'default');
        $this->assertSame(Util::get($this->map, 'name', 'default'), 'John Smith');
        $this->assertSame(Util::get($this->map, 'hobbies', []), ['game', 'outdoor']);
        $this->assertSame(Util::get($this->map, 'hobbies.0', 'default'), 'game');
        $this->assertSame(Util::get($this->map, 'hobbies.3', 'default'), 'default');
        $this->assertSame(Util::get($this->map, 'hobbies.invalid_key', 'default'), 'default');
        $this->assertSame(Util::get($this->map, 'partner.name', 'default'), 'Jane Smith');
        $this->assertSame(Util::get($this->map, 'partner.gender', 'default'), 'female');
        $this->assertSame(Util::get($this->map, 'partner.invalid_key', 'default'), 'default');
        $this->assertSame(Util::get($this->map, 'partner.gender.invalid_key', 'default'), 'default');
        $this->assertSame(Util::get($this->map, 'children', []), []);

        $this->assertSame(Util::get($this->object, 'invalid_key', 'default'), 'default');
        $this->assertSame(Util::get($this->object, 'name', 'default'), 'John Smith');
        $this->assertSame(Util::get($this->object, 'hobbies', []), ['game', 'outdoor']);
        $this->assertSame(Util::get($this->object, 'hobbies.0', 'default'), 'game');
        $this->assertSame(Util::get($this->object, 'hobbies.3', 'default'), 'default');
        $this->assertSame(Util::get($this->object, 'hobbies.invalid_key', 'default'), 'default');
        $this->assertSame(Util::get($this->object, 'partner.name', 'default'), 'Jane Smith');
        $this->assertSame(Util::get($this->object, 'partner.gender', 'default'), 'female');
        $this->assertSame(Util::get($this->object, 'partner.invalid_key', 'default'), 'default');
        $this->assertSame(Util::get($this->object, 'partner.gender.invalid_key', 'default'), 'default');
        $this->assertSame(Util::get($this->object, 'children', []), []);
    }

    public function test_has() {
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
    }

    public function test_set() {
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
    }

    /**
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Nested terminate key undefind_key does not exist.
     */
    public function test_set_undefindKeyArray() {
        Util::set($this->array, 'undefind_key', 'value');
    }

    /**
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Nested terminate key undefind_key does not exist.
     */
    public function test_set_nestedUndefindKeyArray() {
        Util::set($this->map, 'partner.undefind_key', 'value');
    }

    /**
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Nested terminate key undefind_key does not exist.
     */
    public function test_set_undefindKeyObject() {
        Util::set($this->object, 'undefind_key', 'value');
    }

    /**
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Nested terminate key undefind_key does not exist.
     */
    public function test_set_nestedUndefindKeyObject() {
        Util::set($this->object, 'partner.undefind_key', 'value');
    }

    public function test_isNull() {
        $this->assertSame(Util::isNull(null), true);
        $this->assertSame(Util::isNull(false), false);
        $this->assertSame(Util::isNull('false'), false);
        $this->assertSame(Util::isNull(0), false);
        $this->assertSame(Util::isNull('0'), false);
        $this->assertSame(Util::isNull(''), false);
        $this->assertSame(Util::isNull([]), false);
        $this->assertSame(Util::isNull([null]), false);
        $this->assertSame(Util::isNull([1]), false);
        $this->assertSame(Util::isNull('abc'), false);
    }

    public function test_nvl() {
        $this->assertSame(Util::nvl(null, 'default'), 'default');
        $this->assertSame(Util::nvl(false, 'default'), false);
        $this->assertSame(Util::nvl('false', 'default'), 'false');
        $this->assertSame(Util::nvl(0, 'default'), 0);
        $this->assertSame(Util::nvl('0', 'default'), '0');
        $this->assertSame(Util::nvl('', 'default'), '');
        $this->assertSame(Util::nvl([], 'default'), []);
        $this->assertSame(Util::nvl([null], 'default'), [null]);
        $this->assertSame(Util::nvl([1], 'default'), [1]);
        $this->assertSame(Util::nvl('abc', 'default'), 'abc');
    }

    public function test_isBlank() {
        $this->assertSame(Util::isBlank(null), true);
        $this->assertSame(Util::isBlank(false), false);
        $this->assertSame(Util::isBlank('false'), false);
        $this->assertSame(Util::isBlank(0), false);
        $this->assertSame(Util::isBlank('0'), false);
        $this->assertSame(Util::isBlank(''), true);
        $this->assertSame(Util::isBlank([]), true);
        $this->assertSame(Util::isBlank([null]), false);
        $this->assertSame(Util::isBlank([1]), false);
        $this->assertSame(Util::isBlank('abc'), false);
    }

    public function test_bvl() {
        $this->assertSame(Util::bvl(null, 'default'), 'default');
        $this->assertSame(Util::bvl(false, 'default'), false);
        $this->assertSame(Util::bvl('false', 'default'), 'false');
        $this->assertSame(Util::bvl(0, 'default'), 0);
        $this->assertSame(Util::bvl('0', 'default'), '0');
        $this->assertSame(Util::bvl('', 'default'), 'default');
        $this->assertSame(Util::bvl([], 'default'), 'default');
        $this->assertSame(Util::bvl([null], 'default'), [null]);
        $this->assertSame(Util::bvl('abc', 'default'), 'abc');
    }

    public function test_isEmpty() {
        $this->assertSame(Util::isEmpty(null), true);
        $this->assertSame(Util::isEmpty(false), false);
        $this->assertSame(Util::isEmpty('false'), false);
        $this->assertSame(Util::isEmpty(0), true);
        $this->assertSame(Util::isEmpty('0'), false);
        $this->assertSame(Util::isEmpty(''), true);
        $this->assertSame(Util::isEmpty([]), true);
        $this->assertSame(Util::isEmpty([null]), false);
        $this->assertSame(Util::isEmpty([1]), false);
        $this->assertSame(Util::isEmpty('abc'), false);
    }

    public function test_evl() {
        $this->assertSame(Util::evl(null, 'default'), 'default');
        $this->assertSame(Util::evl(false, 'default'), false);
        $this->assertSame(Util::evl('false', 'default'), 'false');
        $this->assertSame(Util::evl(0, 'default'), 'default');
        $this->assertSame(Util::evl('0', 'default'), '0');
        $this->assertSame(Util::evl('', 'default'), 'default');
        $this->assertSame(Util::evl([], 'default'), 'default');
        $this->assertSame(Util::evl([null], 'default'), [null]);
        $this->assertSame(Util::evl('abc', 'default'), 'abc');
    }

    public function test_heredocImplanter() {
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

    public function test_intval() {
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

    public function test_floatval() {
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
