<?php
namespace Rebet\Tests\Common;

use ArrayObject;
use Rebet\Common\Utils;
use Rebet\Tests\Mock\Enum\Gender;
use Rebet\Tests\RebetTestCase;

class UtilsTest extends RebetTestCase
{
    const TEST_VALUE = "UtilsTest::TEST_VALUE";

    public function test_equivalent()
    {
        $this->assertSame(true, Utils::equivalent(null, null));
        $this->assertSame(true, Utils::equivalent(0, 0));
        $this->assertSame(true, Utils::equivalent('', ''));
        $this->assertSame(true, Utils::equivalent([], []));
        $this->assertSame(false, Utils::equivalent(null, 0));
        $this->assertSame(false, Utils::equivalent(null, ''));
        $this->assertSame(false, Utils::equivalent(null, []));
        $this->assertSame(false, Utils::equivalent(0, ''));
        $this->assertSame(false, Utils::equivalent(0, []));
        $this->assertSame(false, Utils::equivalent('', []));

        $this->assertSame(true, Utils::equivalent(1, '1'));
        $this->assertSame(true, Utils::equivalent(1, Gender::MALE()));
        $this->assertSame(true, Utils::equivalent(Gender::MALE(), '1'));
        $this->assertSame(true, Utils::equivalent(Gender::MALE(), Gender::MALE()));
        $this->assertSame(false, Utils::equivalent(1, '2'));
        $this->assertSame(false, Utils::equivalent(2, Gender::MALE()));

        $this->assertSame(true, Utils::equivalent([1, 2, 3], [1, 2, 3]));
        $this->assertSame(true, Utils::equivalent(['1', 2, 3], [1, '2', 3]));
        $this->assertSame(true, Utils::equivalent(['1', 2, 3], [1, Gender::FEMALE(), 3]));
        $this->assertSame(true, Utils::equivalent([1, 2, 3], new ArrayObject([1, 2, 3])));
        $this->assertSame(true, Utils::equivalent(new ArrayObject([1, 2, 3]), new ArrayObject([1, 2, 3])));
        $this->assertSame(true, Utils::equivalent([1, 2, 3], new ArrayObject([1, Gender::FEMALE(), 3])));
        $this->assertSame(false, Utils::equivalent([1, 2, 3], [1, 2]));

        $this->assertSame(true, Utils::equivalent(['a' => 1, 'b' => 2, 'c' => 3], ['a' => 1, 'b' => 2, 'c' => 3]));
        $this->assertSame(true, Utils::equivalent(['a' => 1, 'b' => 2, 'c' => 3], (object)['a' => 1, 'b' => 2, 'c' => 3])); // stdClass can be treated synonymous with array even if it is not iterable
        $this->assertSame(false, Utils::equivalent(['a' => 1, 'b' => 2, 'c' => 3], ['a' => 1, 'b' => 2, 'C' => 3]));
        $object       = new class {
            public $a = 1;
            public $b = 2;
            public $c = 3;
        };
        $this->assertSame(false, Utils::equivalent(['a' => 1, 'b' => 2, 'c' => 3], $object)); // Other class without iterable is not treated synonymously with array

        $this->assertSame(true, Utils::equivalent([1, [2, 3]], [1, [2, 3]]));
        $this->assertSame(true, Utils::equivalent([1, [Gender::FEMALE(), 3]], [Gender::MALE(), new ArrayObject([2, 3])]));
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
        $this->assertSame(3, Utils::coalesce(null, [], '', 3, 'a'));
        $this->assertSame(0, Utils::coalesce(null, [], '', 0, 3, 'a'));
        $this->assertSame('a', Utils::coalesce('a', null, [], '', 0, 3));
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
        $_        = Utils::heredocImplanter();
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
