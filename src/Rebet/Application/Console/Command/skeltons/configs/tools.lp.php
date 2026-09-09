<?php

use Rebet\Application\App;
use Rebet\Tools\Config\Config;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\Math\Decimal;
use Rebet\Tools\Math\Unit;
use Rebet\Tools\Resource\Resource;
use Rebet\Tools\Testable\System;
use Rebet\Tools\Tinker\Tinker;
use Rebet\Tools\Translation\FileDictionary;
use Rebet\Tools\Translation\Translator;
use Rebet\Tools\Utility\Arrays;
use Rebet\Tools\Utility\Env;
use Rebet\Tools\Utility\Namespaces;
use Rebet\Tools\Utility\Securities;
use Rebet\Tools\Utility\Strings;
use Rebet\Tools\Utility\Utils;

/*
|##################################################################################################
| Tools Package Configurations
|##################################################################################################
| This file defines configuration for classes in Rebet\Tools package.
|
| The Rebet configuration file provides multiple ways to describe environment-dependent settings.
| You can use these methods when set environment-dependent settings.
|
| 1. Use `Env::promise('KEY')` to get value from `.env` file for each environment.
| 2. Use `App::when(['env' => value, ...])` to switch value by channel and environment.
| 3. Use `tools@{env}.php` file to override environment dependency value of `tools.php`
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
    | DateTime Configuration
    |==============================================================================================
    | This section defines settings for date/time formatting and parsing.
    | You may change these defaults as required.
    */
    DateTime::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Default Format / Timezone
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | These options define the default output format and timezone used when a DateTime is
        | converted to string (ex: `(string) $datetime`, `$datetime->format()`).
        */
        'default_format'   => 'Y-m-d H:i:s',
        'default_timezone' => Config::refer(App::class, 'timezone', date_default_timezone_get() ?: 'UTC'),


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Acceptable Datetime Format
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Here you may define the list of date/time string formats that `DateTime::analyze()` /
        | `DateTime::valueOf()` try to parse in order, in addition to formats natively supported by
        | PHP's DateTime.
        */
        // 'acceptable_datetime_format' => [
        //     DateTime::ATOM,
        //     DateTime::RFC2822,
        //     'Y-m-d H:i:s.u',
        //     'Y-m-d H:i:s',
        //     'Y/m/d H:i:s',
        //     'YmdHis',
        //     'Y-m-d H:i',
        //     'Y/m/d H:i',
        //     'YmdHi',
        //     'Y-m-d',
        //     'Y/m/d',
        //     'Ymd',
        // ],


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Test Now
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | These options define the fixed "current time" (and its timezone/acceptable formats) that
        | `DateTime::now()` returns while under test, normally set via `DateTime::setTestNow()`
        | rather than editing here directly.
        */
        // 'test_now'          => null,
        // 'test_now_timezone' => null,
        // 'test_now_format'   => ['Y#m#d H:i:s.u', 'Y#m#d H:i:s', 'Y#m#d H:i', 'Y#m#d'],


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Custom Format Characters
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Here you may define custom format characters (that PHP's native date() does not support)
        | usable in `DateTime::format()`, such as localized day-of-week/month names and meridiem.
        */
        // 'custom_formats' => [
        //     '@www' => function (DateTime $datetime) { return $datetime->getDayOfWeek()->translate('label'); },
        //     '@ww'  => function (DateTime $datetime) { return $datetime->getDayOfWeek()->translate('label_short'); },
        //     '@w'   => function (DateTime $datetime) { return $datetime->getDayOfWeek()->translate('label_min'); },
        //     '@mmm' => function (DateTime $datetime) { return $datetime->getLocalizedMonth()->translate('label'); },
        //     '@mm'  => function (DateTime $datetime) { return $datetime->getLocalizedMonth()->translate('label_short'); },
        //     '@a'   => function (DateTime $datetime) { return $datetime->getMeridiem(false); },
        //     '@A'   => function (DateTime $datetime) { return $datetime->getMeridiem(true); },
        // ],
    ],


    /*
    |==============================================================================================
    | Decimal Configuration
    |==============================================================================================
    | This section defines settings for arbitrary precision decimal calculation.
    | You may change these defaults as required.
    */
    Decimal::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Scale Mode / Options
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | This option controls how the calculation scale (number of decimal places) of Decimal is
        | decided, and 'options' defines the parameters used by each mode.
        |
        | Supported Modes:
        |  - Decimal::MODE_FIXED_DECIMAL_PLACES    : Always use 'fixed_scale' decimal places.
        |  - Decimal::MODE_SIGNIFICANCE_ARITHMETIC : Scale is decided by significant figures of the
        |    operands, using 'guard_digits' as extra guard digits for the calculation.
        |  - Decimal::MODE_AUTO_PRECISION_SCALING  : Scale grows automatically as needed, up to
        |    'max_scale'.
        */
        // 'mode'    => Decimal::MODE_AUTO_PRECISION_SCALING,
        // 'options' => [
        //     'fixed_scale'  => 2,  // For MODE_FIXED_DECIMAL_PLACES
        //     'guard_digits' => 4,  // For MODE_FIXED_DECIMAL_PLACES / MODE_SIGNIFICANCE_ARITHMETIC
        //     'max_scale'    => 90, // For MODE_AUTO_PRECISION_SCALING
        // ],
    ],


    /*
    |==============================================================================================
    | Unit Configuration
    |==============================================================================================
    | This section defines the unit conversion factors and formatting options used by Unit.
    | You may change these defaults as required, or add your own units/prefixes.
    */
    Unit::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Unit Factors
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Here you may define the unit factors grouped by physical quantity (Unit::SI_PREFIX,
        | Unit::TIME, Unit::LENGTH, ...). Each factor is [symbol => [factor(or converters), auto_scalable]].
        |
        | Unit Factors Definition:
        | 'factors' => [
        |     'unit_name' => [
        |         'symbole' => ['factor', 'auto_scalable'],
        |         ''        => ['1'     , true           ], // (Base Prefix)
        |     ],
        | ]
        |
        | @see https://en.wikipedia.org/wiki/International_System_of_Units
        | @see https://en.wikipedia.org/wiki/International_System_of_Units#Non-SI_units_accepted_for_use_with_SI
        | @see https://en.wikipedia.org/wiki/Metric_prefix
        | @see https://en.wikipedia.org/wiki/Conversion_of_units
        */
        'factors' => [
            /*
            |--------------------------------------------------------------------------------------
            | The International System of Units Prefix (Metric prefix) Symbol factors
            |--------------------------------------------------------------------------------------
            | Usually you don't need to change these, but you can add/modify your own custom
            | prefixes if you want.
            |
            | Default Configuration: @see Rebet\Tools\Math\Unit::defaultConfig()
            | Base Symbol: '' (empty string) is the base prefix, and its factor is always 1.
            */
            UNIT::SI_PREFIX => [
                // ex) 'M' => ['1e6', true ], // mega
            ],


            /*
            |--------------------------------------------------------------------------------------
            | The Binary Prefix defined in IEEE 1541-2002 Symbol factors
            |--------------------------------------------------------------------------------------
            | Usually you don't need to change these, but you can add/modify your own custom
            | prefixes if you want.
            |
            | Default Configuration: @see Rebet\Tools\Math\Unit::defaultConfig()
            | Base Symbol: '' (empty string) is the base prefix, and its factor is always 1.
            */
            UNIT::BINARY_PREFIX => [
                // ex) 'Mi' => [bcpow('2', '20'), true], // mebi
            ],


            /*
            |--------------------------------------------------------------------------------------
            | Custom Binary Prefixes factors that omitted 'i' from symbols
            |--------------------------------------------------------------------------------------
            | Usually you don't need to change these, but you can add/modify your own custom
            | prefixes if you want.
            |
            | Default Configuration: @see Rebet\Tools\Math\Unit::defaultConfig()
            | Base Symbol: '' (empty string) is the base prefix, and its factor is always 1.
            */
            UNIT::STORAGE_PREFIX => [
                // ex) 'M' => [bcpow('2', '20'), true], // mega
            ],


            /*
            |--------------------------------------------------------------------------------------
            | Time Units include Non-SI units accepted for use with SI and useful units
            |--------------------------------------------------------------------------------------
            | Usually you don't need to change these, but you can add/modify your own custom
            | prefixes if you want.
            | @see https://en.wikipedia.org/wiki/Conversion_of_units#Time
            |
            | Default Configuration: @see Rebet\Tools\Math\Unit::defaultConfig()
            | Base Symbol: 's' (Second) is the base unit, and its factor is always 1.
            */
            UNIT::TIME => [
                // ex) 'd' => ['86400', true ], // Day
            ],


            /*
            |--------------------------------------------------------------------------------------
            | Length Units include Non-SI units accepted for use with SI and useful units
            |--------------------------------------------------------------------------------------
            | Usually you don't need to change these, but you can add/modify your own custom
            | prefixes if you want.
            | @see https://en.wikipedia.org/wiki/Conversion_of_units#Length
            |
            | Default Configuration: @see Rebet\Tools\Math\Unit::defaultConfig()
            | Base Symbol: 'm' (Meter) is the base unit, and its factor is always 1.
            */
            UNIT::LENGTH => [
                // ex) 'cm' => ['1e-2', true ], // centimetre
            ],


            /*
            |--------------------------------------------------------------------------------------
            | Mass Units include Non-SI units accepted for use with SI and useful units
            |--------------------------------------------------------------------------------------
            | Usually you don't need to change these, but you can add/modify your own custom
            | prefixes if you want.
            | @see https://en.wikipedia.org/wiki/Conversion_of_units#Mass
            |
            | Default Configuration: @see Rebet\Tools\Math\Unit::defaultConfig()
            | Base Symbol: 'kg' (Kilogram) is the base unit, and its factor is always 1.
            */
            UNIT::MASS => [
                // ex) 'g' => ['1e-3', true ], // gram
            ],


            /*
            |--------------------------------------------------------------------------------------
            | Electric Current Units include Non-SI units accepted for use with SI and useful units
            |--------------------------------------------------------------------------------------
            | Usually you don't need to change these, but you can add/modify your own custom
            | prefixes if you want.
            | @see https://en.wikipedia.org/wiki/Conversion_of_units#Electric_current
            |
            | Default Configuration: @see Rebet\Tools\Math\Unit::defaultConfig()
            | Base Symbol: 'A' (Ampere) is the base unit, and its factor is always 1.
            */
            UNIT::ELECTRIC_CURRENT => [
                // ex) 'MA' => ['1e6', true ], // megaampere
            ],


            /*
            |--------------------------------------------------------------------------------------
            | Temperature Units include Non-SI units accepted for use with SI and useful units
            |--------------------------------------------------------------------------------------
            | Usually you don't need to change these, but you can add/modify your own custom
            | prefixes if you want.
            | @see https://en.wikipedia.org/wiki/Conversion_of_units#Temperature
            |
            | Default Configuration: @see Rebet\Tools\Math\Unit::defaultConfig()
            | Base Symbol: 'K' (Kelvin) is the base unit, and its factor is always 1.
            */
            UNIT::TEMPERATURE => [
                // ex) 'MK' => ['1e6', true ], // megakelvin
                // ex) '°C' => [[              // Celsius
                //         'from_base' => fn (Decimal $value) => $value->sub('273.15'),
                //         'to_base'   => fn (Decimal $value) => $value->add('273.15'),
                //     ], false],
            ],
        ],


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Formatting Options
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | These options control how `Unit::format()` renders the number/prefix of a unit value.
        | You can modify your own custom options if you want.
        */
        // 'options' => [
        //     'omit_zero'           => true,
        //     'without_prefix'      => false,
        //     'before_prefix'       => '',
        //     'after_prefix'        => '',
        //     'decimal_point'       => '.',
        //     'thousands_separator' => ',',
        // ],
    ],


    /*
    |==============================================================================================
    | Resource Configuration
    |==============================================================================================
    | This section defines the loaders used by `Resource::load()` to read a resource file
    | depending on its file extension.
    */
    Resource::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Resource Loaders
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Here you may define/add a loader [extension => function(string $path, array $option) { ... }]
        | that reads the given resource file path and returns its contents as an array (or null when
        | the file does not exist).
        |
        | Default Loaders Definition:
        |  - php
        |    => Return the result of requirement of specified php file.
        |    => Option: none
        |
        |  - json
        |    => Returns the result of json_decode of the specified json file.
        |    => Option: none
        |
        |  - ini
        |    => Returns the result of parse_ini_file of specified ini file.
        |    => Option:
        |         process_sections => bool          (default: true)
        |         scanner_mode     => INI_SCANNER_* (default: INI_SCANNER_TYPED)
        |
        |  - txt
        |    => Returns the result of explode of the specified txt file.
        |    => Option:
        |         delimiter => string (default: \n)
        |
        | @see Rebet\Tools\Resource\Resource::defaultConfig() for the default loaders.
        */
        'loader' => [
            // --- You can add/override only what you need ---
            // 'yaml' => function(string $path, array $option) : array {
            //     return Symfony\Component\Yaml\Yaml::parse(\file_get_contents($path));
            // }
        ]
    ],


    /*
    |==============================================================================================
    | System Configuration
    |==============================================================================================
    | This section defines the emulators used by System to intercept/emulate PHP built-in functions
    | (that touch global/external state) so they can be controlled/asserted in tests.
    */
    System::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Function Emulators
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Here you may define/add an emulator [function_name => ['emulator' => callable, 'datasets' => array]]
        | that replaces a PHP built-in function's behavior while emulation is enabled via System.
        | The 'emulator' closure receives the same arguments as the original function, and 'datasets'
        | holds the state/fixtures the emulator refers to (accessible via `System::datasets()`).
        |
        | Default Emulators Definition:
        |   - header(string $header, bool $replace = true, int $http_response_code = null)
        |   - headers_list()
        |   - dns_get_record(string $hostname, int $type = DNS_ANY, array|null &$authns = null, array|null &$addtl = null, bool $raw = false) : array
        |
        | @see Rebet\Tools\Testable\System::defaultConfig() for the default emulators.
        */
        'emulators' => [
            // --- You can add/override only what you need ---
        ],
    ],


    /*
    |==============================================================================================
    | Tinker Configuration
    |==============================================================================================
    | This section defines the filters usable via Tinker's `_()`/`__call()` (ex: `$value->upper()`).
    | You may add your own filters as required.
    */
    Tinker::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Delegate Filters
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Here you may delegate filter method names [Class::class => [method, ...]] to the static
        | methods of the given class, using the Tinker's origin value as the first argument.
        */
        'filter' => [
            /*
            |------------------------------------------------------------------------------------
            | Delegate Filters
            |------------------------------------------------------------------------------------
            | Here you may add your own filters [class_name => ['method_name', ...]].
            | You can use Any class methods as filters when the 1st argument is for value.
            |
            | Default Delegates Definition: @see Rebet\Tools\Tinker\Tinker::defaultConfig()
            |   - Reflector::class : convert
            |   - Utils::class     : isBlank, bvl, isEmpty, evl
            |   - Strings::class   : lcut, rcut, clip, indent, ltrim, rtrim, trim, mbtrim,
            |                        startsWith, endsWith, contains, match, wildmatch, split
            |   - Arrays::class    : pluck, override, duplicate, crossJoin, only, except, where,
            |                        compact, unique, first, last, flatten, prepend, shuffle, map,
            |                        reduce, diff, intersect, every, groupBy, union, min, max,
            |                        sort, sortBy, sortKeys, sum, avg, median, mode, implode,
            |                        toQuery
            */
            'delegaters' => [
                // --- You can add/modify only what you need ---
            ],


            /*
            |------------------------------------------------------------------------------------
            | Custom Filters
            |------------------------------------------------------------------------------------
            | Here you may add your own filters [name => function($value, ...$args) { ... }].
            | You can use php built-in functions as filters when the 1st argument is for value.
            |
            | Default Custom Filters Definition: @see Rebet\Tools\Tinker\Tinker::defaultConfig()
            |   - nvl, default, escape, nl2br, datetimef, numberf, stringf, explode, replace,
            |     lower, upper, decimal, abs, eq, gt, gte, lt, lte, add, sub, mul, div, pow,
            |     sqrt, mod, powmod, floor, round, ceil, dump, invoke, equals, sameAs, nnvl,
            |     nbvl, nevl, when, case, length, values, keys
            */
            'customs' => [
                // --- You can add/modify only what you need ---
            ],
        ],
    ],


    /*
    |==============================================================================================
    | File Dictionary Configuration
    |==============================================================================================
    | This section defines the resource directories that FileDictionary (the default Translator
    | dictionary) reads i18n resource files from.
    */
    FileDictionary::class => [
        'resources' => [
            'i18n' => [App::structure()->resources('/i18n')],
        ],
    ],


    /*
    |==============================================================================================
    | Translator Configuration
    |==============================================================================================
    | This section defines settings for message translation.
    | You may change these defaults as required.
    */
    Translator::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Dictionary
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | This option defines the dictionary class that resolves the translation resources, and
        | 'resource_adder' defines how a library's own translation resource is added into it (ex:
        | called from `Translator::addLibraryResource()`).
        */
        'dictionary'     => FileDictionary::class,
        'resource_adder' => [
            FileDictionary::class => function (FileDictionary $dictionary, ...$args) { $dictionary->addLibraryResource(...$args); },
        ],


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Locale / Fallback Locale
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | These options define the current locale used for translation, and the fallback locale
        | used when a message is not found in the current locale.
        |
        | Normally you don't need to change this setting directly here, refer to `App.locale` /
        | `App.fallback_locale` instead.
        */
        'locale'          => Config::refer(App::class, 'locale'),
        'fallback_locale' => Config::refer(App::class, 'fallback_locale'),


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Ordinalize
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Here you may define, per locale, how a number is converted to its ordinal (ex: 1st, 2nd,
        | 3rd) form used by `Translator::ordinalize()`.
        */
        'ordinalize' => [
            // --- You can add/override only what you need for these default ordinalizes ---
            // 'en' => function (int $num) {
            //     return in_array($num % 100, [11, 12, 13]) ? $num.'th' : $num.(['th', 'st', 'nd', 'rd'][$num % 10] ?? 'th');
            // },
        ],
    ],


    /*
    |==============================================================================================
    | Namespaces Configuration
    |==============================================================================================
    | This section defines the `@` namespace aliases resolved by `Namespaces::alias()` (ex: used as
    | the 'namespace' option of ConventionalRoute/MethodRoute, like '@controller').
    */
    Namespaces::class => [
        'aliases' => [
            '@controller' => 'App\\Controller',
        ],
    ],


    /*
    |==============================================================================================
    | Securities Configuration
    |==============================================================================================
    | This section defines settings for hashing and symmetric-key encryption/decryption.
    | Change the secret values via `.env` for each environment, never commit real secrets here.
    */
    Securities::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Hash Settings
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | These options define the salt/pepper/algorithm/stretching used by `Securities::hash()`.
        */
        // 'hash' => [
        //     'salt'       => Env::promise('DEFAULT_HASH_SALT'),
        //     'pepper'     => Env::promise('DEFAULT_HASH_PEPPER'),
        //     'algorithm'  => 'SHA256',
        //     'stretching' => 1,
        // ],


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | HMAC Settings
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | These options define the secret key/algorithm used by `Securities::hmac()`.
        | NOTE: Use a secret key dedicated to HMAC, separate from the 'crypto.secret_key' below, so
        | that the two purposes (message authentication vs symmetric encryption) never share a key.
        */
        // 'hmac' => [
        //     'secret_key' => Env::promise('DEFAULT_HMAC_SECRET_KEY'),
        //     'algorithm'  => 'SHA256',
        // ],


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Crypto Settings
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | These options define the secret key/cipher used by `Securities::encrypt()` /
        | `Securities::decrypt()`.
        */
        // 'crypto' => [
        //     'secret_key' => Env::promise('DEFAULT_CRYPTO_SECRET_KEY'),
        //     'cipher'     => 'AES-256-CBC',
        // ],
    ],
];
