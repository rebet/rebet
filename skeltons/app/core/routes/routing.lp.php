<?php

declare(strict_types=1);

use Rebet\Routing\Route\ConventionalRoute;
use Rebet\Routing\Router;

Router::rules('web')->guard('web')->routing(function (): void {
    Router::default(ConventionalRoute::class);
});
