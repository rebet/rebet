<?php

use Rebet\Application\App;
use Rebet\Routing\Router;

return [
    Router::class => [
        'current_channel'          => App::channel(),
        'default_fallback_handler' => App::kernel()->exceptionHandler(),
    ],
];
