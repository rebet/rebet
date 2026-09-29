<?php

declare(strict_types=1);

use App\Enum\Gender;

return [
    Gender::class => [
        'label' => [
            Gender::MALE()->value   => '男性',
            Gender::FEMALE()->value => '女性',
        ],
    ],
];
