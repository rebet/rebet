<?php
namespace Rebet\Tests\Util;

use PHPUnit\Framework\TestCase;
use Rebet\Util\Util;

class UtilTest extends TestCase {
    const TEST_VALUE = "UtilTest::TEST_VALUE";

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
        $array  = ['a','b','c'];
        $map    = [
            'name' => 'John Smith', 
            'gender' => 'male', 
            'hobbies' => ['game', 'outdoor'], 
            'partner' => [
                'name' => 'Jane Smith', 
                'gender' => 'female'
            ]
        ];
        $object = (object) [
            'name' => 'John Smith', 
            'gender' => 'male', 
            'hobbies' => ['game', 'outdoor'], 
            'partner' => (object)[
                'name' => 'Jane Smith', 
                'gender' => 'female'
            ]
        ];

        $this->assertNull(Util::get(null, 'invalid_key'));
        $this->assertNull(Util::get('string', 'invalid_key'));

        $this->assertNull(Util::get($array, 'invalid_key'));
        $this->assertSame(Util::get($array, 'invalid_key', 'default'), 'default');
        $this->assertSame(Util::get($array, 0, 'default'), 'a');
        $this->assertSame(Util::get($array, 3, 'default'), 'default');

        $this->assertSame(Util::get($map, 'invalid_key', 'default'), 'default');
        $this->assertSame(Util::get($map, 'name', 'default'), 'John Smith');
        $this->assertSame(Util::get($map, 'hobbies', []), ['game', 'outdoor']);
        $this->assertSame(Util::get($map, 'hobbies.0', 'default'), 'game');
        $this->assertSame(Util::get($map, 'hobbies.3', 'default'), 'default');
        $this->assertSame(Util::get($map, 'hobbies.invalid_key', 'default'), 'default');
        $this->assertSame(Util::get($map, 'partner.name', 'default'), 'Jane Smith');
        $this->assertSame(Util::get($map, 'partner.gender', 'default'), 'female');
        $this->assertSame(Util::get($map, 'partner.invalid_key', 'default'), 'default');
        $this->assertSame(Util::get($map, 'partner.gender.invalid_key', 'default'), 'default');

        $this->assertSame(Util::get($object, 'invalid_key', 'default'), 'default');
        $this->assertSame(Util::get($object, 'name', 'default'), 'John Smith');
        $this->assertSame(Util::get($object, 'hobbies', []), ['game', 'outdoor']);
        $this->assertSame(Util::get($object, 'hobbies.0', 'default'), 'game');
        $this->assertSame(Util::get($object, 'hobbies.3', 'default'), 'default');
        $this->assertSame(Util::get($object, 'hobbies.invalid_key', 'default'), 'default');
        $this->assertSame(Util::get($object, 'partner.name', 'default'), 'Jane Smith');
        $this->assertSame(Util::get($object, 'partner.gender', 'default'), 'female');
        $this->assertSame(Util::get($object, 'partner.invalid_key', 'default'), 'default');
        $this->assertSame(Util::get($object, 'partner.gender.invalid_key', 'default'), 'default');
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
