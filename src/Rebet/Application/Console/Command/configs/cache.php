<?php

use Rebet\Cache\Adapter\Symfony\FilesystemAdapter;
use Rebet\Cache\Cache;

return [
    Cache::class => [
        'stores' => [
            'file' => [
                'adapter'                => FilesystemAdapter::class,
                'namespace'              => '',
                'default_lifetime'       => 0,
                'directory'              => null,
                'marshaller'             => null,
                'taggable'               => false,
                'tags_pool'              => null,
                'known_tag_versions_ttl' => 0.15
            ],
        ],
        'default_store' => 'file',
    ],
];
