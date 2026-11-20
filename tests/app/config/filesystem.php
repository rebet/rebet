<?php

declare(strict_types=1);

use League\Flysystem\Local\LocalFilesystemAdapter;
use Rebet\Application\App;
use Rebet\Filesystem\BuiltinFilesystem;
use Rebet\Filesystem\Storage;

return [
    Storage::class => [
        'private_disk' => 'private',
        'public_disk'  => 'public',
        'disks'        => [
            'private' => [
                '@factory' => BuiltinFilesystem::class,
                'adapter'  => [
                    '@factory' => LocalFilesystemAdapter::class,
                    'location' => App::structure()->privateStorage(),
                ],
                'config'   => null,
            ],
            'public'  => [
                '@factory' => BuiltinFilesystem::class,
                'adapter'  => [
                    '@factory' => LocalFilesystemAdapter::class,
                    'location' => App::structure()->publicStorage(),
                ],
                'config'   => [
                    'visibility' => 'public',
                    'url'        => App::structure()->storageUrl(),
                ],
            ],
        ],
    ],
];
