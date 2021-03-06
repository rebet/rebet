<?php
namespace Rebet\Tests\Tools\Utility;

use App\Enum\Gender;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Config\Layer;
use Rebet\Tools\Enum\Enum;
use Rebet\Tools\Exception\LogicException;
use Rebet\Tools\Utility\Callbacks;

class CallbacksTest extends RebetTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
    }

    /**
     * @dataProvider dataTests
     */
    public function test_test($item, $key, $operator, $value, bool $result)
    {
        $test = Callbacks::test($key, $operator, $value);
        $this->assertTrue($test($item) === $result);
    }

    public function dataTests() : array
    {
        $map    = ['age' => 17];
        $object = (object)['age' => 17];
        return [
            [null, null, '===', null , true],

            [123, null, '===', 123  , true],
            [123, null, '===', '123', false],
            [123, null, '===', 456  , false],
            ['b', null, '===', 'a'  , false],
            ['b', null, '===', 'b'  , true],
            ['b', null, '===', 'c'  , false],

            [123, null, '!==', 123  , false],
            [123, null, '!==', '123', true],
            [123, null, '!==', 456  , true],
            ['b', null, '!==', 'a'  , true],
            ['b', null, '!==', 'b'  , false],
            ['b', null, '!==', 'c'  , true],

            [123, null, '==' , 123  , true],
            [123, null, '==' , '123', true],
            [123, null, '==' , 456  , false],
            ['b', null, '==' , 'a'  , false],
            ['b', null, '==' , 'b'  , true],
            ['b', null, '==' , 'c'  , false],

            [123, null, '='  , 123  , true],
            [123, null, '='  , '123', true],
            [123, null, '='  , 456  , false],
            ['b', null, '='  , 'a'  , false],
            ['b', null, '='  , 'b'  , true],
            ['b', null, '='  , 'c'  , false],

            [123, null, '!=' , 123  , false],
            [123, null, '!=' , '123', false],
            [123, null, '!=' , 456  , true],
            ['b', null, '!=' , 'a'  , true],
            ['b', null, '!=' , 'b'  , false],
            ['b', null, '!=' , 'c'  , true],

            [123, null, '<>' , 123  , false],
            [123, null, '<>' , '123', false],
            [123, null, '<>' , 456  , true],
            ['b', null, '<>' , 'a'  , true],
            ['b', null, '<>' , 'b'  , false],
            ['b', null, '<>' , 'c'  , true],

            [123, null, '<' , 122  , false],
            [123, null, '<' , 123  , false],
            [123, null, '<' , 124  , true],
            ['b', null, '<' , 'a'  , false],
            ['b', null, '<' , 'b'  , false],
            ['b', null, '<' , 'c'  , true],

            [123, null, '<=' , 122  , false],
            [123, null, '<=' , 123  , true],
            [123, null, '<=' , 124  , true],
            ['b', null, '<=' , 'a'  , false],
            ['b', null, '<=' , 'b'  , true],
            ['b', null, '<=' , 'c'  , true],

            [123, null, '>' , 122  , true],
            [123, null, '>' , 123  , false],
            [123, null, '>' , 124  , false],
            ['b', null, '>' , 'a'  , true],
            ['b', null, '>' , 'b'  , false],
            ['b', null, '>' , 'c'  , false],

            [123, null, '>=' , 122  , true],
            [123, null, '>=' , 123  , true],
            [123, null, '>=' , 124  , false],
            ['b', null, '>=' , 'a'  , true],
            ['b', null, '>=' , 'b'  , true],
            ['b', null, '>=' , 'c'  , false],

            [$map, 'age'    , '===', 17  , true],
            [$map, 'invalid', '===', 17  , false],
            [$map, 'invalid', '===', null, true],

            [$object, 'age', '===', 17  , true],
        ];
    }

    public function test_test_iInvalidOperator()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Invalid operator <=> given.");

        $test = Callbacks::test(null, '<=>', 12);
        $test(10);
    }

    /**
     * @dataProvider dataCompares
     */
    public function test_compare($a, $b, $key, $invert, int $result)
    {
        $comparator = Callbacks::compare($key, $invert);
        $this->assertSame($result, $comparator($a, $b));
    }

    public function dataCompares() : array
    {
        return [
            [null, null, null, false, 0],
            [null, null, null, true , 0],

            [2, 1, null, false,  1],
            [2, 2, null, false,  0],
            [2, 3, null, false, -1],

            [2, 1, null, true, -1],
            [2, 2, null, true,  0],
            [2, 3, null, true,  1],

            ['b', 'a', null, false,  1],
            ['b', 'b', null, false,  0],
            ['b', 'c', null, false, -1],

            ['b', 'a', null, true, -1],
            ['b', 'b', null, true,  0],
            ['b', 'c', null, true,  1],

            [['a' => 2], ['a' => 1], 'a', false,  1],
            [['a' => 2], ['a' => 2], 'a', false,  0],
            [['a' => 2], ['a' => 3], 'a', false, -1],

            [['a' => 2], ['a' => 1], 'a', true, -1],
            [['a' => 2], ['a' => 2], 'a', true,  0],
            [['a' => 2], ['a' => 3], 'a', true,  1],

            [(object)['a' => 2], (object)['a' => 1], 'a', false,  1],
            [(object)['a' => 2], (object)['a' => 2], 'a', false,  0],
            [(object)['a' => 2], (object)['a' => 3], 'a', false, -1],

            [(object)['a' => 2], (object)['a' => 1], 'a', true, -1],
            [(object)['a' => 2], (object)['a' => 2], 'a', true,  0],
            [(object)['a' => 2], (object)['a' => 3], 'a', true,  1],
        ];
    }

    /**
     * @dataProvider dataRetrievers
     */
    public function test_retriever($value, $retriever, $except)
    {
        $retriever = Callbacks::retriever($retriever);
        $this->assertEquals($except, $retriever($value));
    }

    public function dataRetrievers() : array
    {
        return [
            [null, null, null],

            [123, null, 123],
            ['abc', null, 'abc'],

            [123, function ($v) { return $v * 2; }, 246],
            ['abc', 'mb_strlen', 3],

            [['a' => 'A'], null, ['a' => 'A']],
            [['a' => 'A'], 'a', 'A'],
            [['a' => 'A'], 'b', null],

            [['count' => 123, 'name' => 'foo'], null, ['count' => 123, 'name' => 'foo']],
            [['count' => 123, 'name' => 'foo'], 'count', 2],
            [['count' => 123, 'name' => 'foo'], '@count', 123],
            [['count' => 123, 'name' => 'foo'], 'name', 'foo'],

            [new \ArrayObject(['a' => 'A']), null, new \ArrayObject(['a' => 'A'])],
            [new \ArrayObject(['a' => 'A']), 'a', 'A'],
            [new \ArrayObject(['a' => 'A']), 'b', null],

            [new \ArrayObject(['count' => 123, 'name' => 'foo']), null, new \ArrayObject(['count' => 123, 'name' => 'foo'])],
            [new \ArrayObject(['count' => 123, 'name' => 'foo']), 'count', 2],
            [new \ArrayObject(['count' => 123, 'name' => 'foo']), '@count', 123],
            [new \ArrayObject(['count' => 123, 'name' => 'foo']), 'name', 'foo'],

        ];
    }

    /**
     * @dataProvider dataStringifis
     */
    public function test_stringify($expect, $callable, $verbose)
    {
        $this->assertSame($expect, Callbacks::stringify($callable, $verbose));
    }

    public function dataStringifis() : array
    {
        return [
            ['mb_strlen($str, $encoding)', 'mb_strlen', true ],
            ['mb_strlen($str, $encoding)', 'mb_strlen', false],

            ['Rebet\Tools\Utility\Callbacks::test($key, string $operator, $value) : Closure', Callbacks::class.'::test', true ],
            ['Rebet\Tools\Utility\Callbacks::test($key, string $operator, $value) : Closure', [Callbacks::class, 'test'], true ],
            ['Callbacks::test($key, $operator, $value)', Callbacks::class.'::test', false],

            ['Rebet\Tests\Tools\Utility\CallbacksTest::{closure}()', function () {}, true ],
            ['CallbacksTest::{closure}()'                          , function () {}, false],

            ['Rebet\Tests\Tools\Utility\CallbacksTest::{closure}(?int $i = null, string ...$s) : ?int', function (?int $i = null, string ...$s) : ?int { return $i; }, true ],
            ['CallbacksTest::{closure}($i, ...$s)'                                                    , function (?int $i = null, string ...$s) : ?int { return $i; }, false],

            ['Rebet\Tests\Tools\Utility\CallbacksTest::{closure}(?int $i = null, int $j = 12, int $k = PHP_INT_MAX, string $l = Layer::APPLICATION) : void', function (?int $i = null, int $j = 12, int $k = PHP_INT_MAX, string $l = Layer::APPLICATION) : void {}, true ],
            ['CallbacksTest::{closure}($i, $j, $k, $l)'                                                                                                    , function (?int $i = null, int $j = 12, int $k = PHP_INT_MAX, string $l = Layer::APPLICATION) : void {}, false],

            ['Rebet\Tests\Tools\Utility\CallbacksTest::{closure}(array &$a, string &...$s)', function (array &$a, string &...$s) { }, true ],
            ['CallbacksTest::{closure}(&$a, &...$s)'                                       , function (array &$a, string &...$s) { }, false],

            ['Rebet\Tests\Tools\Utility\CallbacksTest::{closure}(App\Enum\Gender $g) : Rebet\Tools\Enum\Enum', function (Gender $g) : Enum { return $g; } , true ],
            ['CallbacksTest::{closure}($g)'                                                                               , function (Gender $g) : Enum { return $g; } , false],
        ];
    }

    public function test_echoBack()
    {
        $echo_back = Callbacks::echoBack();
        $value     = 'foo';
        $this->assertSame($value, $echo_back($value));
    }

    public function test_compareLength()
    {
        $comparator = Callbacks::compareLength();
        $this->assertSame(-1, $comparator('123', '1234'));
        $this->assertSame(0, $comparator('123', '123'));
        $this->assertSame(1, $comparator('1234', '123'));
    }
}
