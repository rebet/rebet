<?php

use Rebet\Inflection\Inflector;

/*
|##################################################################################################
| Inflection Package Configurations
|##################################################################################################
| This file defines configuration for classes in Rebet\Inflection package.
|
| The Rebet configuration file provides multiple ways to describe environment-dependent settings.
| You can use these methods when set environment-dependent settings.
|
| 1. Use `Env::promise('KEY')` to get value from `.env` file for each environment.
| 2. Use `App::when(['env' => value, ...])` to switch value by channel and environment.
| 3. Use `inflection@{env}.php` file to override environment dependency value of `inflection.php`
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
    | Inflector Configuration
    |==============================================================================================
    | This section defines Inflector settings.
    | You may change these defaults as required.
    |
    | In most cases you will not need to change this setting, but the default setting is not perfect.
    | Therefore, if the word conversion does not work, add or change this setting.
    | 
    | @see Rebet\Inflection\Inflector::defaultConfig() for all of default definitions.
    */
    Inflector::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Conversion Rules Of Pluralize
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Here you can define and customize conversion rules of pluralize if you want.
        | Conversion rules of pluralize are applied and processed in the order `irregular` ->
        | `uninflected` -> `common uninflected` -> `rules`.
        */
        'plural' => [
            /*
            |--------------------------------------------------------------------------------------
            | Basic Rules
            |--------------------------------------------------------------------------------------
            | Basic conversion rules of pluralize are defined by regex.
            | For example,
            |   - ['/(s)tatus$/i', '\1\2tatuses'],
            |   - ['/(matr|vert|ind)(ix|ex)$/i', '\1ices'],
            |   - ['/$/', 's'],
            | and so on.
            |
            | This rule is applied from the top of the definition.
            | NOTE: The custom rules defined here take precedence over the default definitions.
            */
            // 'rules' => [
            //     // ex) ['/regex/i', 'replacement'],
            // ],

            /*
            |--------------------------------------------------------------------------------------
            | Uninflected Rules
            |--------------------------------------------------------------------------------------
            | Uninflected conversion rules of pluralize are defined by regex.
            | For example,
            |   - '.*[nrlm]ese',
            |   - '.*pox',
            |   - 'police',
            | and so on.
            |
            | This rule is applied from the top of the definition.
            | NOTE: The custom rules defined here take precedence over the default definitions.
            */
            // 'uninflected' => [
            //     // ex)'regex',
            // ],

            /*
            |--------------------------------------------------------------------------------------
            | Irregular Rules
            |--------------------------------------------------------------------------------------
            | Irregular conversion rules of pluralize are defined by exact match strings.
            | For example,
            |   - 'atlas'        => 'atlases',
            |   - 'brother'      => 'brothers',
            |   - 'foot'         => 'feet',
            | and so on.
            |
            | This rule is applied from the top of the definition.
            | NOTE: The custom rules defined here append/override the default definitions.
            */
            // 'irregular' => [
            //     // ex) 'singular'     => 'plural',
            // ],
        ],

        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Conversion Rules Of Singularize
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Here you can define and customize conversion rules of singularize if you want.
        | Conversion rules of singularize are applied and processed in the order `irregular` ->
        | `uninflected` -> `rules`.
        */
        'singular' => [
            /*
            |--------------------------------------------------------------------------------------
            | Basic Rules
            |--------------------------------------------------------------------------------------
            | Basic conversion rules of singularize are defined by regex.
            | For example,
            |   - ['/(s)tatuses$/i', '\1\2tatus'],
            |   - ['/(matr)ices$/i', '\1ix'],
            |   - ['/(vert|ind)ices$/i', '\1ex'],
            |   - ['/s$/i', ''],
            | and so on.
            |
            | This rule is applied from the top of the definition.
            | NOTE: The custom rules defined here take precedence over the default definitions.
            */
            // 'rules' => [
            //     // ex) ['/regex/i', 'replacement'],
            // ],

            /*
            |--------------------------------------------------------------------------------------
            | Uninflected Rules
            |--------------------------------------------------------------------------------------
            | Uninflected conversion rules of singularize are defined by regex.
            | For example,
            |   - '.*[nrlm]ese',
            |   - '.*pox',
            |   - 'police',
            | and so on.
            |
            | This rule is applied from the top of the definition.
            | NOTE: The custom rules defined here take precedence over the default definitions.
            */
            // 'uninflected' => [
            //     // ex)'regex',
            // ],

            /*
            |--------------------------------------------------------------------------------------
            | Irregular Rules
            |--------------------------------------------------------------------------------------
            | Irregular conversion rules of singularize are defined by exact match strings.
            | For example,
            |   - 'abuses'       => 'abuse',
            |   - 'emphases'     => 'emphasis',
            |   - 'waves'        => 'wave',
            | and so on.
            |
            | This rule is applied from the top of the definition.
            | NOTE: The custom rules defined here append/override the default definitions.
            */
            // 'irregular' => [
            //     // ex) 'plural'     => 'singular',
            // ],
        ],

        /*
        |--------------------------------------------------------------------------------------
        | Common Uninflected Rules
        |--------------------------------------------------------------------------------------
        | Common uninflected conversion rules both of pluralize and singularize are defined by
        | regex.
        | For example,
        |   - '.*?media',
        |   - 'bison',
        |   - 'sheep',
        | and so on.
        |
        | This rule is applied from the top of the definition.
        | NOTE: The custom rules defined here take precedence over the default definitions.
        */
        'uninflected' => [
            // ex) 'regex',
        ]
    ],
];
