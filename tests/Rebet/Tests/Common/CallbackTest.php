<?php
namespace Rebet\Tests\Common;

use Rebet\Common\Callback;
use Rebet\Tests\RebetTestCase;

class CallbackTest extends RebetTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * @dataProvider dataTests
     */
    public function test_test($item, $key, $operator, $value, bool $result)
    {
        $test = Callback::test($key, $operator, $value);
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

    /**
     * @expectedException Rebet\Common\Exception\LogicException
     * @expectedExceptionMessage Invalid operator <=> given.
     */
    public function test_test_iInvalidOperator()
    {
        $test = Callback::test(null, '<=>', 12);
        $test(10);
        $this->fail('Never execute.');
    }

    /**
     * @dataProvider dataCompares
     */
    public function test_compare($a, $b, $key, $invert, int $result)
    {
        $comparator = Callback::compare($key, $invert);
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
        $retriever = Callback::retriever($retriever);
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

            [(object)['a' => 'A'], null, (object)['a' => 'A']],
            [(object)['a' => 'A'], 'a', 'A'],
            [(object)['a' => 'A'], 'b', null],

            [(object)['count' => 123, 'name' => 'foo'], null, (object)['count' => 123, 'name' => 'foo']],
            [(object)['count' => 123, 'name' => 'foo'], 'count', 1],
            [(object)['count' => 123, 'name' => 'foo'], '@count', 123],
            [(object)['count' => 123, 'name' => 'foo'], 'name', 'foo'],

        ];
    }
}
