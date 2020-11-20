<?php

use Rebet\Cache\Adapter\Symfony\FilesystemAdapter;
use Rebet\Cache\Cache;

return [
    Cache::class => [
        'stores' => [
            'file' => [
                'adapter' => [
                    '@factory' => FilesystemAdapter::class,
                ],
            ],
        ],
        'default_store' => 'file',
    ],
];
