<?php

declare(strict_types=1);

use Rebet\Tests\Tools\Enum\EnumerableTest_Gender;

// For Enumerable test (the translation group is overridden by EnumerableTest_* enums)
return [
    EnumerableTest_Gender::class => [
        'label' => [
            1 => 'Männlich',
            2 => 'Weiblich',
        ],
    ],
];
