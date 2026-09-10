<?php

use Rebet\Application\App;
use Rebet\Routing\Route\ConventionalRoute;
use Rebet\Routing\Route\MethodRoute;
use Rebet\Routing\Router;
use Rebet\Routing\ViewSelector;

/*
|##################################################################################################
| Routing Package Configurations
|##################################################################################################
| This file defines configuration for classes in Rebet\Routing package.
|
| The Rebet configuration file provides multiple ways to describe environment-dependent settings.
| You can use these methods when set environment-dependent settings.
|
| 1. Use `Env::promise('KEY')` to get value from `.env` file for each environment.
| 2. Use `App::when(['env' => value, ...])` to switch value by channel and environment.
| 3. Use `routing@{env}.php` file to override environment dependency value of `routing.php`
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
    | Router Configuration
    |==============================================================================================
    | This section defines routing settings.
    | You may change these defaults as required.
    */
    Router::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Global Middlewares
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Here you may define the middlewares that are run for every route of the given channel
        | (web, api, console, ...), in addition to any middlewares defined on the route itself.
        | These middlewares run before the middlewares defined on the matched route.
        |
        | Supported Options:
        |  - @see Rebet\Middleware\Routing\AddGlobalShareVariableToView
        |  - @see Rebet\Middleware\Routing\AddQueuedCookiesToResponse
        |  - @see Rebet\Middleware\Routing\Authenticate
        |  - @see Rebet\Middleware\Routing\EmptyStringToNull
        |  - @see Rebet\Middleware\Routing\RestoreInheritData
        |  - @see Rebet\Middleware\Routing\SetRequestInputDataToView
        |  - @see Rebet\Middleware\Routing\StartSession
        |  - @see Rebet\Middleware\Routing\TrimStrings
        |  - @see Rebet\Middleware\Routing\VerifyCsrfToken
        |  and also you can use any middleware class that you want.
        */
        'middlewares' => [
            'web' => [
                Rebet\Middleware\Routing\AddQueuedCookiesToResponse::class,
                Rebet\Middleware\Routing\StartSession::class,
                Rebet\Middleware\Routing\RestoreInheritData::class,
                Rebet\Middleware\Routing\TrimStrings::class,
                Rebet\Middleware\Routing\EmptyStringToNull::class,
                [Rebet\Middleware\Routing\VerifyCsrfToken::class, 'excludes' => [], 'is_support_xsrf' => false, 'xsrf_lifetime' => null],
                Rebet\Middleware\Routing\Authenticate::class,
                Rebet\Middleware\Routing\AddGlobalShareVariableToView::class,
                Rebet\Middleware\Routing\SetRequestInputDataToView::class,
            ],
        ],


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Current Channel
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | This option defines the current inflow route/application invoke interface, like web, api,
        | console, ... . It is used by `Router::getCurrentChannel()`, and also referenced when
        | resolving the 'middlewares' settings above and any per-channel routing rules.
        |
        | Normally you don't need to change this setting, it is automatically set from the current
        | application/kernel.
        */
        'current_channel' => App::channel(),


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Default Fallback Handler
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | This option defines the fallback action that is called when routing/action processing
        | throws an exception and no fallback registered via `Router::rules()->fallback()` handles
        | it (or none was registered at all).
        |
        | Normally you don't need to change this setting, it is automatically set to the current
        | kernel's exception handler.
        */
        'default_fallback_handler' => App::kernel()->exceptionHandler(),
    ],


    /*
    |==============================================================================================
    | View Selector Configuration
    |==============================================================================================
    | This section defines settings for selecting/changing the view to be rendered depending on the
    | current request and authenticated user (ex: device or role dependent view switching).
    */
    ViewSelector::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | View Changer
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Here you may define a callback that changes the view name to be rendered depending on the
        | current request and authenticated user.
        | function(string $view_name, Request $request, AuthUser $user):string|string[]
        |
        | ex) 'changer' => function ($view_name, $request, $user) { return $request->isMobile() ? "mobile/{$view_name}" : $view_name; },
        */
        'changer' => null,
    ],


    /*
    |==============================================================================================
    | Conventional Route Configuration
    |==============================================================================================
    | This section defines settings for `Router::default()` conventional routing, that resolves
    | `{controller}/{action}/{arg1}/{arg2}...` style URI to `{Controller}@{action}({arg1}, {arg2}, ...)`.
    */
    ConventionalRoute::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Controller Namespace
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | This option defines the namespace where controller classes are resolved from.
        | You can use `@` namespace alias (ex: '@controller') defined by `Namespaces::alias()`.
        */
        'namespace' => '@controller',


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Default Controller/Action
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | These options define the default controller/action part name used when the corresponding
        | URI part is omitted.
        */
        'default_part_of_controller' => 'top',
        'default_part_of_action'     => 'index',


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | URI Snake Separator
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | This option defines the separator used when converting a snake/kebab case URI controller
        | part (ex: 'user-account') to a pascal case controller class name (ex: 'UserAccount').
        */
        'uri_snake_separator' => '-',


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Controller/Action Suffix
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | These options define the suffix appended to the resolved controller class name and action
        | method name.
        */
        'controller_suffix' => 'Controller',
        'action_suffix'     => '',


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Aliases
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Here you may define URI part aliases [alias => real controller/action path] for the case
        | when you want to expose a URI that is different from the actual controller/action name.
        |
        | ex) 'aliases' => ['user' => 'user-account/list'],
        */
        'aliases' => [],


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Accessible
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Normally, only public methods of the controller are routable as actions.
        | If you set this option true, then non-public (protected/private) methods also become
        | routable. It is not recommended unless you have a specific reason.
        */
        'accessible' => false,
    ],


    /*
    |==============================================================================================
    | Method Route Configuration
    |==============================================================================================
    | This section defines settings for `Router::match()` (get/post/put/patch/delete/options/any)
    | declarative routing, that resolves given `'Controller::action'` string style action.
    */
    MethodRoute::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Controller Namespace
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | This option defines the namespace where controller classes are resolved from.
        | You can use `@` namespace alias (ex: '@controller') defined by `Namespaces::alias()`.
        */
        'namespace' => '@controller',
    ],
];
