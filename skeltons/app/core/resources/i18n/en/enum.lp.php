<?php
declare(strict_types=1);

use App\Enum\Gender;

return [
    Gender::class => [
        'label' => [
            Gender::MALE()->value   => 'Male',
            Gender::FEMALE()->value => 'Female',
        ],
    ],
];
