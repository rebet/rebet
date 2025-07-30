<?php

use Rebet\Application\App;
use Rebet\Log\Driver\Monolog\TestDriver;
use Rebet\Log\Log;
use Rebet\Log\LogLevel;

return [
    Log::class => [
        'unittest' => true,
        'default_channel' => App::channel() ?? 'stderr',
        'channels' => [
            'web' => [
                'driver' => [
                    '@factory' => TestDriver::class,
                    'level'    => LogLevel::DEBUG,
                ],
            ],
        ],
    ],
];
