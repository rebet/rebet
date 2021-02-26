<?php
return [
    '@full_name' => ':first_name :last_name',
    '@delimiter' => ', ',
    '@errors'    => [
        'class' => 'is-danger',
        'color' => ['red', '#333'],
    ],

    'http' => [
        404 => [
            'title'  => 'Custom Not Found',
            'detail' => 'The page could not be found. The specified URL is incorrect, or the page may have already been deleted / moved.',
        ]
    ],

    'welcome' => 'Hello, :name.',
    "sample"  => [
        "[1]   This is an apple.",
        "[2,*] There are :amount apples.",
    ],
];
