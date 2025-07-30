<?php

use Rebet\Application\App;
use Rebet\Tools\Config\Config;
use Rebet\Tools\Translation\FileDictionary;
use Rebet\Tools\Translation\Translator;
use Rebet\Tools\Utility\Namespaces;

return [
    DateTime::class => [
        'default_timezone' => Config::refer(App::class, 'timezone', date_default_timezone_get() ? : 'UTC'),
    ],
    
    Namespaces::class => [
        'aliases' => [
            '@app'        => 'App',
            '@controller' => '@app\\Controller',
            '@model'      => '@app\\Model',
            '@stub'       => '@app\\Stub',
        ],
    ],

    Translator::class => [
        'locale'          => Config::refer(App::class, 'locale'),
        'fallback_locale' => Config::refer(App::class, 'fallback_locale'),
    ],

    FileDictionary::class => [
        'resources' => [
            'i18n' => [App::structure()->resources('/i18n')],
        ]
    ],
];
