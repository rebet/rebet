<?php

use Rebet\Application\View\Engine\Blade\BladeTagCustomizer;
use Rebet\Application\View\Engine\Twig\TwigTagCustomizer;
use Rebet\View\Engine\Blade\Blade;
use Rebet\View\Engine\Twig\Twig;

return [
    Blade::class => [
        'customizers' => [BladeTagCustomizer::class.'::customize'],
    ],

    Twig::class => [
        'customizers' => [TwigTagCustomizer::class.'::customize'],
    ],
];
