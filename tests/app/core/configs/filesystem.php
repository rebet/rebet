<?php

use League\Flysystem\Adapter\Local;
use Rebet\Application\App;
use Rebet\Filesystem\Storage;

return [
    Storage::class => [
        'private_disk' => 'private',
        'public_disk'  => 'public',
        'disks'        => [
            'private'  => [
                'adapter'    => [
                    '@factory' => Local::class,
                    'root'     => App::structure()->privateStorage(),
                ],
                'config' => null,
            ],
            'public' => [
                'adapter'    => [
                    '@factory' => Local::class,
                    'root'     => App::structure()->publicStorage(),
                ],
                'config' => [
                    'visibility' => 'public',
                    'url'        => App::structure()->storageUrl(),
                ],
            ],
        ],
    ],
];
