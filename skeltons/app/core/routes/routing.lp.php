<?php
declare(strict_types=1);

use Rebet\Routing\Route\ConventionalRoute;
use Rebet\Routing\Router;

Router::rules('web')->guard('web')->routing(function () {
    Router::default(ConventionalRoute::class);
});
