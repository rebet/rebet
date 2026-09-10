<?php

use Rebet\Application\App;
use Rebet\Application\View\Engine\Blade\BladeTagCustomizer;
use Rebet\Application\View\Engine\Twig\TwigTagCustomizer;
//{%-- if $view == 'blade' -%}
use Rebet\View\Engine\Blade\Blade;
//{%-- endif -%}
//{%-- if $view == 'twig' -%}
use Rebet\View\Engine\Twig\Twig;
//{%-- endif -%}
use Rebet\View\EofLineFeed;
use Rebet\View\View;

/*
|##################################################################################################
| View Package Configurations
|##################################################################################################
| This file defines configuration for classes in Rebet\View package.
|
| The Rebet configuration file provides multiple ways to describe environment-dependent settings.
| You can use these methods when set environment-dependent settings.
|
| 1. Use `Env::promise('KEY')` to get value from `.env` file for each environment.
| 2. Use `App::when(['env' => value, ...])` to switch value by channel and environment.
| 3. Use `view@{env}.php` file to override environment dependency value of `view.php`
|
| You can also use `Config::refer()` to refer to the settings of other classes, use
| `Config::promise()` to get the settings by lazy evaluation, and have the values evaluated each
| time the settings are referenced.
|
| NOTE: If you want to get other default setting samples of configuration file, try check here.
|       https://github.com/rebet/rebet/tree/master/skeltons/app/core/configs
*/
return [
    /*
    |==============================================================================================
    | View Configuration
    |==============================================================================================
    | This section defines settings for view rendering.
    | You may change these defaults as required.
    */
    View::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Rendering Engine
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | This option defines the fixed view engine class name used to render every view.
        | When null, the engine is resolved per view (ex: by file extension) instead of being fixed.
        |
        | Supported:
        |  - @see Rebet\View\Engine\Blade\Blade
        |  - @see Rebet\View\Engine\Twig\Twig
        |  - and also you can use any view engine class that implemented Rebet\View\Engine\Engine.
        */
        //{%-- if $view == 'blade' -%}
        'engine' => Blade::class, //{#-- @phpstan-ignore array.duplicateKey (-#})
        //{%-- elseif $view == 'twig' -%}
        'engine' => Twig::class,
        //{%-- else -%}
        'engine' => null,
        //{%-- endif -%}


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | EOF Line Feed
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | This option controls how the trailing CR/LF of a rendered view is treated.
        |
        | Supported:
        |  - EofLineFeed::KEEP() : Keep the rendered content as it is.
        |  - EofLineFeed::TRIM() : Trim trailing CR/LF.
        |  - EofLineFeed::ONE()  : Trim trailing CR/LF then append exactly one LF.
        */
        // 'eof_line_feed' => EofLineFeed::TRIM(),
    ],


    /*
    |==============================================================================================
    | Blade Engine Configuration
    |==============================================================================================
    | This section defines settings for Laravel Blade view engine.
    | You may change these defaults as required.
    */
    Blade::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | View / Cache Path
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | 'view_path' defines the directory (or directories) that Blade view files (*.blade.php) are
        | resolved from, and 'cache_path' defines the directory the compiled view cache is stored in
        | (it will be created automatically if it does not exist).
        */
        'view_path'  => [App::path('/core/views')],
        'cache_path' => App::path('/var/cache/views/blade'),


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Customizers
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Here you may define callables `function(BladeCompiler $compiler) : void` that customize
        | the Blade compiler (ex: registering your own `@directive`).
        */
        'customizers' => [BladeTagCustomizer::class.'::customize'],
    ],


    /*
    |==============================================================================================
    | Twig Engine Configuration
    |==============================================================================================
    | This section defines settings for Twig view engine.
    | You may change these defaults as required.
    */
    Twig::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Template Directory
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | This option defines the directory (or directories) that Twig template files are resolved
        | from.
        */
        'template_dir' => [App::path('/core/views')],


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Environment Options
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Here you may set any options supported by Twig's own Environment (ex: 'cache', 'debug',
        | 'auto_reload', 'strict_variables', 'autoescape').
        |
        | @see https://twig.symfony.com/doc/3.x/api.html#environment-options
        */
        'options' => [],


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Customizers
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Here you may define callables `function(Environment $twig) : void` that customize the Twig
        | environment (ex: registering your own tags/filters/functions).
        */
        'customizers' => [TwigTagCustomizer::class.'::customize'],


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | File Suffix
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | This option defines the file extension appended to a view name when resolving Twig
        | template files.
        */
        'file_suffix' => '.twig',
    ],
];
