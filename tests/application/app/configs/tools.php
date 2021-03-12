<?php

use Rebet\Tools\Utility\Namespaces;

return [
    Namespaces::class => [
        'aliases' => [
            '@app'        => 'App',
            '@controller' => '@app\\Controller',
            '@model'      => '@app\\Model',
            '@stub'       => '@app\\Stub',
        ],
    ],
];
