<?php
namespace Rebet\Tests\Common;

use Rebet\Tests\RebetTestCase;
use Rebet\Common\ArrayUtil;

class ArrayUtilTest extends RebetTestCase {
    public function test_randomSelect() {
        $list = ['a','b','c','d','e','f'];
        sort($list);
        $size = count($list);
        for ($i=0; $i <= $size; $i++) { 
            [$actual_winner, $actual_loser] = ArrayUtil::randomSelect($list, $i);
            $this->assertSame($i, count($actual_winner));
            $this->assertSame($size - $i, count($actual_loser));
            $combined_actual = array_merge($actual_winner, $actual_loser);
            sort($combined_actual);
            $this->assertSame($list, array_values($combined_actual));
        }
    }

    public function test_isSequential() {
        $this->assertFalse(ArrayUtil::isSequential(null));

        $this->assertTrue(ArrayUtil::isSequential([]));
        $this->assertTrue(ArrayUtil::isSequential([1]));
        $this->assertTrue(ArrayUtil::isSequential([1,2,3]));
        $this->assertTrue(ArrayUtil::isSequential([0 => 'a', 1 => 'b', 2 => 'c']));
        $this->assertTrue(ArrayUtil::isSequential([0 => 'a', '1' => 'b']));

        $this->assertFalse(ArrayUtil::isSequential([0 => 'a', 2 => 'c', 1 => 'b']));
        $this->assertFalse(ArrayUtil::isSequential([1 => 'c', 2 => 'b']));
        $this->assertFalse(ArrayUtil::isSequential([0 => 'a', 'a' => 'b']));
        $this->assertFalse(ArrayUtil::isSequential(['a' => 'a', 'b' => 'b']));
    }

    public function test_flatten() {
        $this->assertNull(ArrayUtil::flatten(null));
        $this->assertSame([], ArrayUtil::flatten([]));
        $this->assertSame([1, 2], ArrayUtil::flatten([1, 2]));
        $this->assertSame([1, 2, 3], ArrayUtil::flatten([1, 2, [3]]));
        $this->assertSame([1, 2, 3, 4], ArrayUtil::flatten([1, 2, [3, [4]]]));
        $this->assertSame([1, 2, 3, 4, 5], ArrayUtil::flatten([1, 2, [3, [4], 5]]));
        $this->assertSame([1, 2, 3, 4, 5, 6], ArrayUtil::flatten([1, 2, [3, [4], 5, [], 6]]));
    }

    public function test_remap() {
        $list = [
            ['user_id' => 21, 'name' => 'John'],
            ['user_id' => 35, 'name' => 'David'],
            ['user_id' => 43, 'name' => 'Linda'],
        ];

        $this->assertSame([], ArrayUtil::remap(null, null, 'user_id'));
        $this->assertSame([], ArrayUtil::remap([], null, 'user_id'));
        $this->assertSame([21, 35, 43], ArrayUtil::remap($list, null, 'user_id'));
        $this->assertSame([21 => 'John', 35 => 'David', 43 => 'Linda'], ArrayUtil::remap($list, 'user_id', 'name'));
        $this->assertSame([
                21 => ['user_id' => 21, 'name' => 'John'],
                35 => ['user_id' => 35, 'name' => 'David'],
                43 => ['user_id' => 43, 'name' => 'Linda']
            ], 
            ArrayUtil::remap($list, 'user_id', null)
        );
    }
    
    public function test_override() {
        $this->assertSame(1, ArrayUtil::override(null, 1));
        $this->assertNull(ArrayUtil::override(1, null));
        $this->assertSame([1, 2], ArrayUtil::override(1, [1, 2]));
        $this->assertSame(1, ArrayUtil::override([1, 2], 1));
        $this->assertSame([3], ArrayUtil::override([1, 2], [3]));
        $this->assertSame([3], ArrayUtil::override(['a' => 1, 'b' => 2], [3]));
        $this->assertSame(
            ['a' => 3, 'b' => 2],
            ArrayUtil::override(['a' => 1, 'b' => 2], ['a' => 3])
        );
        $this->assertSame(
            ['a' => 3, 'b' => 2, 'c' => 3],
            ArrayUtil::override(['a' => 1, 'b' => 2], ['a' => 3, 'c' => 3])
        );
        $this->assertSame(
            ['a' => 3, 'b' => [2], 'c' => 3],
            ArrayUtil::override(['a' => [1], 'b' => [2]], ['a' => 3, 'c' => 3])
        );
        $this->assertSame(
            ['a' => ['A' => 3, 'B' => 2, 'C' => 3], 'b' => 2, 'c' => 3],
            ArrayUtil::override(['a' => ['A' => 1, 'B' => 2], 'b' => 2], ['a' => ['A' => 3, 'C' => 3], 'c' => 3])
        );
        $this->assertSame(
            ['a' => ['A' => ['α' => 1], 'B' => 2, 'C' => 3], 'b' => 2, 'c' => 3],
            ArrayUtil::override(['a' => ['A' => 1, 'B' => 2], 'b' => 2], ['a' => ['A' => ['α' => 1], 'C' => 3], 'c' => 3])
        );
        $this->assertSame(
            ['a' => [], 'b' => 2, 'c' => 3],
            ArrayUtil::override(['a' => ['A' => 1, 'B' => 2], 'b' => 2], ['a' => [], 'c' => 3])
        );
        $this->assertSame(
            ['a' => [['A' => 3]], 'b' => 2, 'c' => 3],
            ArrayUtil::override(['a' => [['A' => 1], ['B' => 2]], 'b' => 2], ['a' => [['A' => 3]], 'c' => 3])
        );
    }
}
