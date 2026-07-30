<?php

use League\Flysystem\Local\LocalFilesystemAdapter;
use Rebet\Application\App;
use Rebet\Filesystem\Storage;

return [
    Storage::class => [
        'private_disk' => 'private',
        'public_disk'  => 'public',
        'disks'        => [
            'private' => [
                'adapter' => [
                    '@factory' => LocalFilesystemAdapter::class,
                    'location' => App::structure()->privateStorage(),
                ],
                'config' => null,
            ],
            'public' => [
                'adapter' => [
                    '@factory' => LocalFilesystemAdapter::class,
                    'location' => App::structure()->publicStorage(),
                ],
                'config' => [
                    'visibility' => 'public',
                    'url'        => App::structure()->storageUrl(),
                ],
            ],
        ],
    ],
];
