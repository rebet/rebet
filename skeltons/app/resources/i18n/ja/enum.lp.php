<?php

declare(strict_types=1);

use App\Enum\Gender;

return [
    Gender::class => [
        'label' => [
            Gender::Male->value   => '男性',
            Gender::Female->value => '女性',
        ],
    ],
];
