<?php

declare(strict_types=1);

use App\Enum\Gender;

return [
    Gender::class => [
        'label' => [
            Gender::Male->value   => 'Male',
            Gender::Female->value => 'Female',
        ],
    ],
];
