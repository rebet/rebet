<?php

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
        'customizers' => [],
    ],

    Twig::class => [
        'template_dir' => [],
        'options'      => [],
        'customizers'  => [],
        'file_suffix'  => '.twig',
    ],
];
