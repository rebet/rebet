<?php
return [
    '@full_name' => ':last_name :first_name',
    '@delimiter' => '／',
    '@errors'    => [
        'class' => 'is-danger',
        'color' => ['red', '#333'],
    ],

    'http' => [
        404 => [
            'title'  => '指定のページが見つかりません',
            'detail' => 'ご指定のページは見つかりませんでした。ご指定のURLが間違っているか、既にページが削除／移動された可能性があります。',
        ]
    ],

    'welcome' => 'ようこそ、:name様',
    "sample"  => [
        "[1]   これはリンゴです。",
        "[2,*] これらは:amount個のリンゴです。",
    ],
];
