<?php

use App\Enum\Gender;
use Rebet\Validation\Kind;

return [
    Gender::class => [
        'label' => [
            1 => '男性',
            2 => '女性',
        ],
    ],

    // This enum not translatable
    Kind::class => [
        'label' => [
            1 => '整合性チェック',
            2 => '依存性チェック',
            3 => 'その他',
        ],
    ]
];
