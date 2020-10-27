<?php

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem as FlysystemFilesystem;
use Rebet\Filesystem\BuiltinFilesystem;
use Rebet\Filesystem\Storage;

return [
    BuiltinFilesystem::class => [
        'driver'  => FlysystemFilesystem::class,
    ],

    Storage::class => [
        'filesystem'   => BuiltinFilesystem::class,
        'private_disk' => 'private',
        'public_disk'  => 'public',
        'disks'        => [
            'private'  => [
                'adapter'      => Local::class,
                'root'         => null,

                // === Optional Config Settings ===
                // 'writeFlags'   => LOCK_EX,
                // 'linkHandling' => Local::DISALLOW_LINKS,
                // 'permissions'  => [],

                // === Filesystem Global Configuration ===
                'filesystem'   => null,
            ],
            'public' => [
                'adapter'      => Local::class,
                'root'         => null,

                // === Optional Config Settings ===
                // 'writeFlags'   => LOCK_EX,
                // 'linkHandling' => Local::DISALLOW_LINKS,
                // 'permissions'  => [],

                // === Filesystem Global Configuration ===
                'filesystem'   => [
                    'visibility' => 'public',
                    'url'        => null,
                ],
            ],
            // // Sample of ftp settings
            // 'ftp' => [
            //     'adapter' => Ftp::class,
            //     'config'  => [
            //         'host'     => null,
            //         'username' => null,
            //         'password' => null,
            //
            //         // // optional config settings
            //         // 'port'                 => 21,
            //         // 'root'                 => '/path/to/root',
            //         // 'passive'              => true,
            //         // 'ssl'                  => true,
            //         // 'timeout'              => 30,
            //         // 'ignorePassiveAddress' => false,
            //     ],
            // ],
        ],
    ],
];
