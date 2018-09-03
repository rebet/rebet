<?php
namespace Rebet\Tests\Util;

use PHPUnit\Framework\TestCase;
use Rebet\Util\ArrayUtil;

class ArrayUtilTest extends TestCase {
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

    public function test_isSequentialArray() {
        $this->assertFalse(ArrayUtil::isSequentialArray(null));

        $this->assertTrue(ArrayUtil::isSequentialArray([]));
        $this->assertTrue(ArrayUtil::isSequentialArray([1]));
        $this->assertTrue(ArrayUtil::isSequentialArray([1,2,3]));
        $this->assertTrue(ArrayUtil::isSequentialArray([0 => 'a', 1 => 'b', 2 => 'c']));
        $this->assertTrue(ArrayUtil::isSequentialArray([0 => 'a', '1' => 'b']));

        $this->assertFalse(ArrayUtil::isSequentialArray([0 => 'a', 2 => 'c', 1 => 'b']));
        $this->assertFalse(ArrayUtil::isSequentialArray([1 => 'c', 2 => 'b']));
        $this->assertFalse(ArrayUtil::isSequentialArray([0 => 'a', 'a' => 'b']));
        $this->assertFalse(ArrayUtil::isSequentialArray(['a' => 'a', 'b' => 'b']));
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
}
