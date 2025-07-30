<?php
use Rebet\Application\App;
use Rebet\Application\Console\Assistant;
use Rebet\Database\Pagination\Pager;
use Rebet\Http\Request;
use Rebet\Tools\Utility\Env;

/*
|##################################################################################################
| Application Package Configurations
|##################################################################################################
| This file defines configuration for classes in Rebet\Application package.
| (and specific settings of your application)
|
| The Rebet configuration file provides multiple ways to describe environment-dependent settings.
| You can use these methods when set environment-dependent settings.
|
| 1. Use `Env::promise('KEY')` to get value from `.env` file for each environment.
| 2. Use `App::when(['env' => value, ...])` to switch value by channel and environment.
| 3. Use `application@{env}.php` file to override environment dependency value of `application.php`
|
| You can also use `Config::refer()` to refer to the settings of other classes, use
| `Config::promise()` to get the settings by lazy evaluation, and have the values evaluated each
| time the settings are referenced.
|
| NOTE: If you want to get other default setting samples of configuration file, try check here.
|       https://github.com/rebet/rebet/tree/master/src/Rebet/Application/Console/Command/skeltons/configs
*/
return [
    /*
    |==============================================================================================
    | Application Configuration
    |==============================================================================================
    | This section defines common settings that affect the entire application and specific user
    | settings that the application is free to use.
    | Common settings (locale, etc.) are referenced from each package configuration and the
    | settings are linked.
    */
    App::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Application Code Name
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | The application code name.
        | Since this value may be used for directories, file names, prefixes of various key names,
        | etc., so set it using only half-width alphanumeric characters (including '_' and '-').
        */
        'code_name' => '{! $code_name !}',


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Application Domain
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | The application domain.
        | The settings of domain in libary layer use `localhost`.
        */
        'domain' => Env::promise('APP_DOMAIN', 'localhost'),


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Application Locale
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | The application locale determines the default locale that will be used by the translation
        | package. You are free to set this value to any of the locales which will be supported by
        | the application.
        | The settings of locale in libary layer use `locale_get_default()`, so you don't need to
        | set this configuration if you are set 'intl.default_locale' in php.ini.
        */
        'locale' => '{! $locale !}',


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Application Fallback Locale
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | The fallback locale determines the locale to use when the current one is not available.
        | You may change the value to correspond to any of the language folders that are provided
        | through your application.
        */
        'fallback_locale' => 'en',


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Application Timezone
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Here you may specify the default timezone for your application, which will be used by
        | DateTime class and others.
        | The settings of timezone in libary layer use `date_default_timezone_get()`, so you don't
        | need to set this configuration if you are set 'date.timezone' in php.ini.
        */
        'timezone' => '{! $timezone !}',


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Pagination Settings
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Here you may specify settings related to pagination across multiple packages such as
        | 'View' -> (Http) -> 'Database' -> 'View'.
        | You are free to change these settings as needed.
        */
        'paginate' => [
            /*
            |--------------------------------------------------------------------------------------
            | Page Number Property Name
            |--------------------------------------------------------------------------------------
            | Page number property name of GET/POST queries.
            |
            | NOTE: This setting for 'paginate' tag for view.
            */
            'page_name' => 'page',


            /*
            |--------------------------------------------------------------------------------------
            | Page Size Property Name
            |--------------------------------------------------------------------------------------
            | Page size property name of GET/POST queries.
            |
            | You can also remove this if you don't want the user to resize the page.
            | In that case, please also correct the following items.
            |  - Removed `->size(...)` setting process of 'paginate.resolver' configuration
            |
            | NOTE: This setting for Rebet\Database\Pagination\Pager::class resolver.
            */
            'page_size_name' => 'page_size',


            /*
            |--------------------------------------------------------------------------------------
            | Default Page Navigation Template
            |--------------------------------------------------------------------------------------
            | Default paginate view template name.
            |
            | Available Templates:
            |  - paginate@bootstrap-4, paginate@semantic-ui, paginate@bulma, paginate@default
            |  - and custom template that you are created
            |
            | NOTE: This setting for 'paginate' tag for view.
            */
            'default_template' => 'paginate@bootstrap-4',


            /*
            |--------------------------------------------------------------------------------------
            | Default Page Size
            |--------------------------------------------------------------------------------------
            | Please change if necessary.
            |
            | NOTE: This setting for Rebet\Database\Pagination\Pager::class.
            */
            'default_page_size' => 10,


            /*
            |--------------------------------------------------------------------------------------
            | Max Page Size
            |--------------------------------------------------------------------------------------
            | Please change if necessary.
            |
            | NOTE: This setting for Rebet\Database\Pagination\Pager::class.
            */
            'max_page_size' => 100,


            /*
            |--------------------------------------------------------------------------------------
            | Default Each Side Page Count For Page Navigation
            |--------------------------------------------------------------------------------------
            | Please change if necessary.
            | When current page is 7th then
            |  - Set 0:         | < | 7 | > |
            |  - Set 1:     | < | 6 | 7 | 8 | > |
            |  - Set 2: | < | 5 | 6 | 7 | 8 | 9 | > |
            |
            | NOTE: This setting for Rebet\Database\Pagination\Pager::class, but it will influence
            |       'paginate' tag behavior of view.
            */
            'default_each_side' => 0,


            /*
            |--------------------------------------------------------------------------------------
            | Need Calculate Total Page or Not
            |--------------------------------------------------------------------------------------
            | If you set 'true' this option, you can become to use 'total count' and 'go to last page'.
            | However, please note that the page processing performance will deteriorate because the
            | cost of data counting will be extra to realize this processing.
            |
            | NOTE: This setting for Rebet\Database\Pagination\Pager::class, but it will influence
            |       'paginate' tag behavior of view.
            */
            'default_need_total' => false,


            /*
            |--------------------------------------------------------------------------------------
            | Paging Information Resolver
            |--------------------------------------------------------------------------------------
            | Here we define a resolver to automatically resolve the information needed for paging.
            | As the default definition, the information required for page processing is automatically
            | set using the values of HTTP request parameters (usually 'page' and 'page_size').
            |
            | You can customize this as needed, For example,
            |  - `$pager->needTotal($request->get('need_total') ?? false)`
            | can be used to change the page feed behavior by user specification, or
            |  - `$pager->needTotal(Auth::user()->paginate_need_total ?? false)`
            | can also be defined as a user customization item, such as.
            |
            | NOTE: This setting for Rebet\Database\Pagination\Pager::class
            */
            'resolver' => function (Pager $pager) {
                $request = Request::current();
                return $pager
                    ->page($request->get(App::config('paginate.page_name')) ?? 1)
                    ->size($request->get(App::config('paginate.page_size_name')) ?? App::config('paginate.default_page_size'))
                    ;
            },
        ],


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Application User Specific Configurations
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | You are free to define any settings as needed.
        | And you can use it by `App::config('item_name')`.
        */
        // You can write specific settings of your application to here.
    ],



    /*
    |==============================================================================================
    | Console Application Configuration
    |==============================================================================================
    | This section defines console application settings about rebet assistant.
    */
    Assistant::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Console Application Commands
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Here you may defines console application commands to use via rebet assistant.
        | For example, by implementing the periodical execution job required by the application as a
        | command and registering it here, it can be executed like `rebet app:data-clean`.
        |
        | Preinstalled Commands:
        |  - Rebet\Application\Console\Command\InitCommand::class
        |  - Rebet\Application\Console\Command\EnvCommand::class
        |  - Rebet\Application\Console\Command\HashPasswordCommand::class
        */
        'commands' => [
            // YourApplicationJobCommand::class,
        ],
    ],
];
