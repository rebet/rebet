<?php

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem as FlysystemFilesystem;
use Rebet\Filesystem\BuiltinFilesystem;
use Rebet\Filesystem\Storage;
use Rebet\Tools\Utility\Path;

return [
    BuiltinFilesystem::class => [
        'driver' => FlysystemFilesystem::class,
    ],

    Storage::class => [
        'filesystem'   => BuiltinFilesystem::class,
        'private_disk' => 'private',
        'public_disk'  => 'public',
        'disks'        => [
            'private'  => [
                'adapter'    => [
                    '@factory' => Local::class,
                    'root'     => Path::normalize(sys_get_temp_dir().'/rebet/strage/private'),
                ],
                'filesystem' => null,
            ],
            'public' => [
                'adapter'    => [
                    '@factory' => Local::class,
                    'root'     => Path::normalize(sys_get_temp_dir().'/rebet/strage/public'),
                ],
                'filesystem' => [
                    'visibility' => 'public',
                    'url'        => null,
                ],
            ],
        ],
    ],
];
