<?php

declare(strict_types=1);

use Rebet\Cache\Adapter\Symfony\ArrayAdapter;
use Rebet\Cache\Cache;

return [
    Cache::class => [
        'stores='       => [
            'array' => [
                'adapter' => ArrayAdapter::class,
            ],
        ],
        'default_store' => 'array',
    ],
];
