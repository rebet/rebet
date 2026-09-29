<?php

declare(strict_types=1);

use Rebet\Routing\Router;

//---------------------------------------------
// Routing Settings
//---------------------------------------------
Router::rules('web')->guard('web')->roles('user')->routing(function (): void {
    Router::get('/hello', fn() => "Hello World.");
});
