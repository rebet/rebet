<?php
namespace Rebet\Tests\Common;

use Rebet\Tests\RebetTestCase;
use Rebet\Common\Arrays;
use Rebet\Common\OverrideOption;

class ArraysTest extends RebetTestCase
{
    public function test_randomSelect()
    {
        $list = ['a','b','c','d','e','f'];
        sort($list);
        $size = count($list);
        for ($i=0; $i <= $size; $i++) {
            [$actual_winner, $actual_loser] = Arrays::randomSelect($list, $i);
            $this->assertSame($i, count($actual_winner));
            $this->assertSame($size - $i, count($actual_loser));
            $combined_actual = array_merge($actual_winner, $actual_loser);
            sort($combined_actual);
            $this->assertSame($list, array_values($combined_actual));
        }
    }

    public function test_isSequential()
    {
        $this->assertFalse(Arrays::isSequential(null));

        $this->assertTrue(Arrays::isSequential([]));
        $this->assertTrue(Arrays::isSequential([1]));
        $this->assertTrue(Arrays::isSequential([1,2,3]));
        $this->assertTrue(Arrays::isSequential([0 => 'a', 1 => 'b', 2 => 'c']));
        $this->assertTrue(Arrays::isSequential([0 => 'a', '1' => 'b']));

        $this->assertFalse(Arrays::isSequential([0 => 'a', 2 => 'c', 1 => 'b']));
        $this->assertFalse(Arrays::isSequential([1 => 'c', 2 => 'b']));
        $this->assertFalse(Arrays::isSequential([0 => 'a', 'a' => 'b']));
        $this->assertFalse(Arrays::isSequential(['a' => 'a', 'b' => 'b']));
    }

    public function test_flatten()
    {
        $this->assertNull(Arrays::flatten(null));
        $this->assertSame([], Arrays::flatten([]));
        $this->assertSame([1, 2], Arrays::flatten([1, 2]));
        $this->assertSame([1, 2, 3], Arrays::flatten([1, 2, [3]]));
        $this->assertSame([1, 2, 3, 4], Arrays::flatten([1, 2, [3, [4]]]));
        $this->assertSame([1, 2, 3, 4, 5], Arrays::flatten([1, 2, [3, [4], 5]]));
        $this->assertSame([1, 2, 3, 4, 5, 6], Arrays::flatten([1, 2, [3, [4], 5, [], 6]]));
    }

    public function test_remap()
    {
        $list = [
            ['user_id' => 21, 'name' => 'John'],
            ['user_id' => 35, 'name' => 'David'],
            ['user_id' => 43, 'name' => 'Linda'],
        ];

        $this->assertSame([], Arrays::remap(null, null, 'user_id'));
        $this->assertSame([], Arrays::remap([], null, 'user_id'));
        $this->assertSame([21, 35, 43], Arrays::remap($list, null, 'user_id'));
        $this->assertSame([21 => 'John', 35 => 'David', 43 => 'Linda'], Arrays::remap($list, 'user_id', 'name'));
        $this->assertSame(
            [
                21 => ['user_id' => 21, 'name' => 'John'],
                35 => ['user_id' => 35, 'name' => 'David'],
                43 => ['user_id' => 43, 'name' => 'Linda']
            ],
            Arrays::remap($list, 'user_id', null)
        );
    }
    
    public function test_override()
    {
        $this->assertSame(1, Arrays::override(null, 1));
        $this->assertNull(Arrays::override(1, null));
        $this->assertSame([1, 2], Arrays::override(1, [1, 2]));
        $this->assertSame(1, Arrays::override([1, 2], 1));
        $this->assertSame([1, 2, 3], Arrays::override([1, 2], [3]));
        $this->assertSame([3, 1, 2], Arrays::override([1, 2], [3], [], OverrideOption::PREPEND));
        $this->assertSame([3], Arrays::override([1, 2], [3], [], OverrideOption::REPLACE));
        $this->assertSame([3], Arrays::override(['a' => 1, 'b' => 2], [3]));
        $this->assertSame(
            ['a' => 3, 'b' => 2],
            Arrays::override(['a' => 1, 'b' => 2], ['a' => 3])
        );
        $this->assertSame(
            ['a' => 3, 'b' => 2, 'c' => 3],
            Arrays::override(['a' => 1, 'b' => 2], ['a' => 3, 'c' => 3])
        );
        $this->assertSame(
            ['a' => 3, 'b' => [2], 'c' => 3],
            Arrays::override(['a' => [1], 'b' => [2]], ['a' => 3, 'c' => 3])
        );
        $this->assertSame(
            ['a' => ['A' => 3, 'B' => 2, 'C' => 3], 'b' => 2, 'c' => 3],
            Arrays::override(['a' => ['A' => 1, 'B' => 2], 'b' => 2], ['a' => ['A' => 3, 'C' => 3], 'c' => 3])
        );
        $this->assertSame(
            ['a' => ['A' => ['α' => 1], 'B' => 2, 'C' => 3], 'b' => 2, 'c' => 3],
            Arrays::override(['a' => ['A' => 1, 'B' => 2], 'b' => 2], ['a' => ['A' => ['α' => 1], 'C' => 3], 'c' => 3])
        );
        $this->assertSame(
            ['a' => [], 'b' => 2, 'c' => 3],
            Arrays::override(['a' => ['A' => 1, 'B' => 2], 'b' => 2], ['a' => [], 'c' => 3])
        );
        $this->assertSame(
            ['a' => [['A' => 1], ['B' => 2], ['A' => 3]], 'b' => 2, 'c' => 3],
            Arrays::override(['a' => [['A' => 1], ['B' => 2]], 'b' => 2], ['a' => [['A' => 3]], 'c' => 3])
        );

        $this->assertSame(
            [
                'map'   => ['a' => ['A' => 'A', 'B' => 'B'], 'b' => 'b', 'c' => 'C'],
                'array' => ['a', 'b', 'c'],
            ],
            Arrays::override(
                [
                    'map'   => ['a' => ['A' => 'A'], 'b' => 'b'],
                    'array' => ['a', 'b'],
                ],
                [
                    'map'   => ['a' => ['B' => 'B'], 'c' => 'C'],
                    'array' => ['c'],
                ]
            )
        );
    }

    public function test_override_option()
    {
        $this->assertSame(
            [
                'map'   => ['a' => ['B' => 'B'], 'b' => 'b', 'c' => 'C'],
                'array' => ['c'],
            ],
            Arrays::override(
                [
                    'map'   => ['a' => ['A' => 'A'], 'b' => 'b'],
                    'array' => ['a', 'b'],
                ],
                [
                    'map'   => ['a' => ['B' => 'B'], 'c' => 'C'],
                    'array' => ['c'],
                ],
                [
                    'map'   => ['a' => OverrideOption::REPLACE],
                    'array' => OverrideOption::REPLACE,
                ]
            )
        );
    }

    public function test_override_optionInline()
    {
        $this->assertSame(
            [
                'map'   => ['a' => ['B' => 'B'], 'b' => 'b', 'c' => 'C'],
                'array' => ['c'],
            ],
            Arrays::override(
                [
                    'map'   => ['a' => ['A' => 'A'], 'b' => 'b'],
                    'array' => ['a', 'b'],
                ],
                [
                    'map'   => ['a!' => ['B' => 'B'], 'c' => 'C'],
                    'array!' => ['c'],
                ]
            )
        );
    }

    public function test_override_defaultModePrepend()
    {
        $this->assertSame(
            [
                'array' => ['c', 'a', 'b'],
            ],
            Arrays::override(
                [
                    'array' => ['a', 'b'],
                ],
                [
                    'array' => ['c'],
                ],
                [],
                OverrideOption::PREPEND
            )
        );

        $this->assertSame(
            [
                'array' => ['c', 'a', 'b'],
            ],
            Arrays::override(
                [
                    'array' => ['a', 'b'],
                ],
                [
                    'array<' => ['c'],
                ],
                [],
                OverrideOption::PREPEND
            )
        );

        $this->assertSame(
            [
                'array' => ['a', 'b', 'c'],
            ],
            Arrays::override(
                [
                    'array' => ['a', 'b'],
                ],
                [
                    'array>' => ['c'],
                ],
                [],
                OverrideOption::PREPEND
            )
        );

        $this->assertSame(
            [
                'array' => ['c'],
            ],
            Arrays::override(
                [
                    'array' => ['a', 'b'],
                ],
                [
                    'array!' => ['c'],
                ],
                [],
                OverrideOption::PREPEND
            )
        );
    }

    public function test_override_defaultModeReplace()
    {
        $this->assertSame(
            [
                'array' => ['c'],
            ],
            Arrays::override(
                [
                    'array' => ['a', 'b'],
                ],
                [
                    'array' => ['c'],
                ],
                [],
                OverrideOption::REPLACE
            )
        );
    }
}
