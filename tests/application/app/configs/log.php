<?php

use Rebet\Log\Driver\Monolog\TestDriver;
use Rebet\Log\Log;
use Rebet\Log\LogLevel;

return [
    Log::class => [
        'unittest' => true,
        'channels' => [
            'web' => [
                'driver' => [
                    '@factory' => TestDriver::class,
                    'name'     => 'web',
                    'level'    => LogLevel::DEBUG,
                ],
            ],
        ],
    ],
];
