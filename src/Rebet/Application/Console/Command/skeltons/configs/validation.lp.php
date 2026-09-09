<?php

use Rebet\Validation\BuiltinValidations;
use Rebet\Validation\Validator;

/*
|##################################################################################################
| Validation Package Configurations
|##################################################################################################
| This file defines configuration for classes in Rebet\Validation package.
|
| The Rebet configuration file provides multiple ways to describe environment-dependent settings.
| You can use these methods when set environment-dependent settings.
|
| 1. Use `Env::promise('KEY')` to get value from `.env` file for each environment.
| 2. Use `App::when(['env' => value, ...])` to switch value by channel and environment.
| 3. Use `validation@{env}.php` file to override environment dependency value of `validation.php`
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
    | Builtin Validations Configuration
    |==============================================================================================
    | This section defines settings referenced by the built-in validation rules.
    | You may change these defaults as required.
    */
    BuiltinValidations::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Custom Validations
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Here you may add your own custom validation rules
        | [name => function(Context $c, ...$args) : bool { ... }].
        | You can use `validation{Name}` naming convention to call `$name`.
        */
        'customs' => [],


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Default Settings For Built-in Validations
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Here you may define default option values [ValidationName => [option => value, ...]] that
        | are used when the corresponding option is omitted from the validation rule definition.
        */
        'default' => [
            /*
            |------------------------------------------------------------------------------------
            | DependenceChar Validation Settings
            |------------------------------------------------------------------------------------
            | This option defines the default character encoding used to detect platform/vendor
            | dependent characters (ex: 'sjis-win' for Windows-31J machine-dependent characters).
            */
            // 'DependenceChar' => [
            //     'encode' => 'sjis-win'
            // ],


            /*
            |------------------------------------------------------------------------------------
            | NgWord Validation Settings
            |------------------------------------------------------------------------------------
            | These options control how NG words are matched against the input text, allowing
            | detection even when the word is disguised by inserted delimiters/omission characters,
            | full/half-width variants, and other visually ambiguous characters.
            |
            |  - word_split_pattern : Regex character class of delimiters that can be inserted
            |    between each character of an NG word without avoiding detection (ex: 'b a d' for
            |    NG word 'bad').
            |  - delimiter_pattern  : Regex character class of characters treated as delimiters
            |    when counting the omission distance/ratio below.
            |  - omission_pattern   : Regex character class of characters that can be freely
            |    inserted/omitted between each character of an NG word (ex: marks, symbols).
            |  - omission_length    : Number of leading characters of the NG word exempted from the
            |    'omission_ratio' tolerance check (always strictly matched).
            |  - omission_ratio     : Maximum ratio (0.0 - 1.0) of omission characters allowed
            |    within the remainder of the NG word before it is no longer considered a match.
            |  - ambiguous_patterns : [character => regex] map of visually/phonetically ambiguous
            |    character variants (ex: full-width, circled, Japanese kana) that should also match
            |    the corresponding character of an NG word.
            */
            // 'NgWord' => [
            //     'word_split_pattern' => '[\p{Z}\p{P}]',
            //     'delimiter_pattern'  => '[\p{Common}]',
            //     'omission_pattern'   => '[\p{M}\p{S}〇*＊_＿]',
            //     'omission_length'    => 3,
            //     'omission_ratio'     => 0.4,
            //     'ambiguous_patterns' => [
            //         // All of default configures @see Rebet\Validation\BuiltinValidations::defaultConfig()
            //         // ex) "c" => "([cCƆɔↃↄꜾꜿĈĉČčĊċÇçḈḉȻȼƇƈɕｃＣⒸⓒ🄲🅒🅲©])",
            //     ],
            // ],
        ],
    ],


    /*
    |==============================================================================================
    | Validator Configuration
    |==============================================================================================
    | This section defines settings for the validation process itself.
    | You may change these defaults as required.
    */
    Validator::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Validations
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | This option defines the class name that implements Validations, providing the set of
        | validation rule methods that can be referenced from validation rule definitions.
        |
        | Supported:
        |  - @see Rebet\Validation\BuiltinValidations
        |  - and also you can use any class that implemented Rebet\Validation\Validations.
        */
        // 'validations' => BuiltinValidations::class,


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Nested Attribute Auto Format
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | When true, a nested attribute name (ex: 'items.*.name') used in an error message is
        | automatically formatted to a human friendly label (ex: 'Nth Items Name') using the
        | ordinal number of the nested position.
        */
        // 'nested_attribute_auto_format' => true,
    ],
];
