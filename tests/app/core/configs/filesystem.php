<?php

use League\Flysystem\Adapter\Local;
use Rebet\Application\App;
use Rebet\Filesystem\Storage;
use Rebet\Tools\Config\Config;

return [
    Storage::class => [
        'private_disk' => 'private',
        'public_disk'  => 'public',
        'disks'        => [
            'private'  => [
                'adapter'    => [
                    '@factory' => Local::class,
                    'root'     => Config::promise(fn() => App::structure()->privateStorage()),
                ],
                'config' => null,
            ],
            'public' => [
                'adapter'    => [
                    '@factory' => Local::class,
                    'root'     => Config::promise(fn() => App::structure()->publicStorage()),
                ],
                'config' => [
                    'visibility' => 'public',
                    'url'        => Config::promise(fn() => App::structure()->storageUrl()),
                ],
            ],
        ],
    ],
];
