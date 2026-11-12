<?php

declare(strict_types=1);

use Rebet\Tests\Tools\Enum\EnumerableTest_Base;
use Rebet\Tests\Tools\Enum\EnumerableTest_Child;
use Rebet\Tests\Tools\Enum\EnumerableTest_EmptyFields;
use Rebet\Tests\Tools\Enum\EnumerableTest_Gender;
use Rebet\Tests\Tools\Enum\EnumerableTest_Grandchild;
use Rebet\Tests\Tools\Enum\EnumerableTest_Multi;
use Rebet\Tests\Tools\Enum\EnumerableTest_Own;
use Rebet\Tests\Tools\Enum\EnumerableTest_Season;

// For Enumerable test (the translation group is given by the Translatable attribute of EnumerableTest_* enums)
return [
    // backed enum: the key is value
    EnumerableTest_Gender::class      => [
        'label' => [
            1 => '男性',
            2 => '女性',
        ],
    ],

    // pure enum: the key is case name
    EnumerableTest_Season::class      => [
        'label' => [
            'Summer' => '夏',
        ],
    ],

    // Translatable: label and label_short are translated, but label_min is not (even if the dictionary has it)
    EnumerableTest_Multi::class       => [
        'label'       => [0 => '日曜日', 1 => '月曜日'],
        'label_short' => [0 => '日', 1 => '月'],
        'label_min'   => [0 => '日(最小)', 1 => '月(最小)'],
    ],

    // Translatable: empty fields (the dictionary is not used)
    EnumerableTest_EmptyFields::class => [
        'label' => [0 => '日曜日'],
    ],

    // Inheritance of the Translatable attribute (the key is the called class)
    EnumerableTest_Base::class        => [
        'label'       => [1 => '基底'],
        'label_short' => [1 => '基'],
    ],
    EnumerableTest_Child::class       => [
        'label'       => [1 => '子'],
        'label_short' => [1 => '子短'],
    ],
    EnumerableTest_Grandchild::class  => [
        'label'       => [1 => '孫'],
        'label_short' => [1 => '孫短'],
    ],
    EnumerableTest_Own::class         => [
        'label'       => [1 => '独自'],
        'label_short' => [1 => '独自短'],
    ],
];
