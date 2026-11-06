<?php

declare(strict_types=1);

use Rebet\Tests\Tools\Enum\EnumerableTest_Gender;
use Rebet\Tests\Tools\Enum\EnumerableTest_Season;

// For Enumerable test (the translation group is overridden by EnumerableTest_* enums)
return [
    // backed enum: the key is value
    EnumerableTest_Gender::class => [
        'label' => [
            1 => '男性',
            2 => '女性',
        ],
    ],

    // pure enum: the key is case name
    EnumerableTest_Season::class => [
        'label' => [
            'Summer' => '夏',
        ],
    ],
];
