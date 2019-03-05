<?php
return [
    '@errors' => [
        'class' => 'is-danger',
        'color' => ['red', '#333'],
    ],

    'http' => [
        404 => [
            'title'  => 'Custom Not Found',
            'detail' => 'The page could not be found. The specified URL is incorrect, or the page may have already been deleted / moved.',
        ]
    ],
];
