<?php

use Rebet\Application\App;
use Rebet\Routing\Route\ConventionalRoute;
use Rebet\Routing\Route\MethodRoute;
use Rebet\Routing\Router;
use Rebet\Routing\ViewSelector;

return [
    Router::class => [
        'middlewares'              => [],
        'current_channel'          => App::channel(),
        'default_fallback_handler' => App::kernel()->exceptionHandler(),
    ],

    ViewSelector::class => [
        'changer' => null,
    ],

    ConventionalRoute::class => [
        'namespace'                  => '@controller',
        'default_part_of_controller' => 'top',
        'default_part_of_action'     => 'index',
        'uri_snake_separator'        => '-',
        'controller_suffix'          => 'Controller',
        'action_suffix'              => '',
        'aliases'                    => [],
        'accessible'                 => false,
    ],

    MethodRoute::class => [
        'namespace' => '@controller',
    ],
];
