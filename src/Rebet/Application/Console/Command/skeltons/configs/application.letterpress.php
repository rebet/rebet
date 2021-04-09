<?php
use Rebet\Application\App;
use Rebet\Application\Console\Assistant;

return [
    /*
    |==============================================================================================
    | Application Configuration
    |==============================================================================================
    | This section defines common settings that affect the entire application and specific user
    | settings that the application is free to use.
    | Common settings (locale, etc.) are referenced from each module by the framework layer and the
    | settings are linked.
    | (details @see Rebet\Application\Bootstrap\LoadFrameworkConfiguration)
    */
    App::class => [
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
        |
        |  page_name        : Page number property name of GET/POST queries. (default: 'page')
        |  page_size_name   : Page size   property name of GET/POST queries. (default: 'page_size')
        |  default_template : Default paginate view template name.
        |
        | Available template: 'paginate@bootstrap-4', 'paginate@semantic-ui', 'paginate@bulma',
        |                     'paginate@default' and custom template that you are created
        */
        'paginate' => [
            // 'page_name'        => 'page',
            // 'page_size_name'   => 'page_size',
            'default_template' => 'paginate@bootstrap-4',
        ],


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Application User Specific Configurations
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | You are free to define any settings as needed.
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
        | NOTE: Preinstalled commands are you can find in Assistant::defaultConfig().
        */
        'commands' => [
            // YourApplicationJobCommand::class,
        ],
    ],
];
