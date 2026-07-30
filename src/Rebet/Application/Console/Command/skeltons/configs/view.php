<?php

use Rebet\Application\View\Engine\Blade\BladeTagCustomizer;
use Rebet\Application\View\Engine\Twig\TwigTagCustomizer;
use Rebet\View\Engine\Blade\Blade;
use Rebet\View\Engine\Twig\Twig;
use Rebet\View\EofLineFeed;
use Rebet\View\View;

return [
    View::class => [
        'engine'        => null,
        'eof_line_feed' => EofLineFeed::TRIM()
    ],

    Blade::class => [
        'view_path'   => [],
        'cache_path'  => null,
        'customizers' => [BladeTagCustomizer::class.'::customize'],
    ],

    Twig::class => [
        'template_dir' => [],
        'options'      => [],
        'customizers'  => [TwigTagCustomizer::class.'::customize'],
        'file_suffix'  => '.twig',
    ],
];
