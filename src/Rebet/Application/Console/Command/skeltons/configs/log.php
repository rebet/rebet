<?php

use Monolog\Processor\ProcessIdProcessor;
use Rebet\Log\Driver\Monolog\Formatter\TextFormatter;
use Rebet\Log\Driver\Monolog\MonologDriver;
use Rebet\Log\Driver\Monolog\StderrDriver;
use Rebet\Log\Driver\Monolog\TestDriver;
use Rebet\Log\Log;
use Rebet\Log\LogLevel;
use Rebet\Tools\Utility\Strings;

return [
    Log::class => [
        'unittest'         => false,
        'unittest_channel' => 'test',
        'default_channel'  => 'stderr',
        'channels'         => [
            'stderr' => [
                'driver' => [
                    '@factory' => StderrDriver::class,
                    'name'     => 'stderr',
                    'level'    => LogLevel::DEBUG,
                ],
            ],
            'test' => [
                'driver' => [
                    '@factory' => TestDriver::class,
                    'name'     => 'test',
                    'level'    => LogLevel::DEBUG,
                ],
            ],
        ],
        'fallback_log'     => defined('STDERR') ? STDERR : 'php://stderr',
    ],

    MonologDriver::class => [
        'processors' => [
            ProcessIdProcessor::class,
        ],
    ],

    TextFormatter::class => [
        'default_format'      => "{datetime} {channel}/{extra.process_id} [{level_name}] {message}{context}{extra}{exception}\n",
        'default_stringifier' => function ($val, array $masks, string $masked_label) { return Strings::stringify($val, $masks, $masked_label); },
        'stringifiers'        => [
            '{datetime}'  => function ($val, array $masks, string $masked_label) { return $val->format('Y-m-d H:i:s.u'); },
            '{context}'   => function ($val, array $masks, string $masked_label) { return empty($val) ? '' : "\n====== [  CONTEXT  ] ======\n".Strings::indent(Strings::stringify($val, $masks, $masked_label), "== ") ; },
            '{extra}'     => function ($val, array $masks, string $masked_label) { return empty($val) ? '' : "\n------ [   EXTRA   ] ------\n".Strings::indent(Strings::stringify($val, $masks, $masked_label), "-- ") ; },
            '{exception}' => function ($val, array $masks, string $masked_label) { return empty($val) ? '' : "\n****** [ EXCEPTION ] ******\n".Strings::indent("{$val}", "** ") ; },
        ],
        'masks'               => [],
        'masked_label'        => '********',
    ],
];
