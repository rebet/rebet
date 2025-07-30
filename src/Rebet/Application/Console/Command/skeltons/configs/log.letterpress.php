<?php

use Monolog\Processor\ProcessIdProcessor;
use Rebet\Application\App;
use Rebet\Log\Driver\Monolog\FileDriver;
use Rebet\Log\Driver\Monolog\Formatter\TextFormatter;
use Rebet\Log\Driver\Monolog\MonologDriver;
use Rebet\Log\Driver\Monolog\StderrDriver;
use Rebet\Log\Driver\Monolog\TestDriver;
use Rebet\Log\Driver\StackDriver;
use Rebet\Log\Log;
use Rebet\Log\LogLevel;
use Rebet\Tools\Utility\Strings;

/*
|##################################################################################################
| Log Package Configurations
|##################################################################################################
| This file defines configuration for classes in Rebet\Log package.
|
| The Rebet configuration file provides multiple ways to describe environment-dependent settings.
| You can use these methods when set environment-dependent settings.
|
| 1. Use `Env::promise('KEY')` to get value from `.env` file for each environment.
| 2. Use `App::when(['env' => value, ...])` to switch value by channel and environment.
| 3. Use `log@{env}.php` file to override environment dependency value of `log.php`
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
    | Log Configuration
    |==============================================================================================
    | This section defines Log settings.
    | You may change these defaults as required.
    */
    Log::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Unit Test Mode
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | This setting is unit testing mode, so it should be set true while you are running unit
        | tests by `Log::unittest(true)` or overridden in configuration for unit test.
        | If you set this mode to be true, so all of log channels become using unittest_channel's
        | driver. The default setting is to use TestDriver during unit testing (changeable).
        */
        // 'unittest' => false,

        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Unit Test Log Channel
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | The channel name for unit testing when unittest mode is true (default: 'test').
        | Normally you don't need to change this setting.
        */
        // 'unittest_channel' => 'test',

        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Default Log Channel
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | The default log channel name set same as route channel name.
        | Normally you don't need to change this setting.
        */
        'default_channel' => App::channel() ?? 'stderr',

        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Log Channels
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Here you may configure the log channels for your application.
        | Out of the box, Rebet uses the Monolog PHP logging library. This gives you a variety of 
        | powerful log handlers / formatters to utilize.
        |
        | Provided Drivers:
        | - Rebet\Log\Driver\NullDriver::class
        | - Rebet\Log\Driver\StackDriver::class
        | - Rebet\Log\Driver\Monolog\FileDriver::class    (extends MonologDriver)
        | - Rebet\Log\Driver\Monolog\MonologDriver::class
        | - Rebet\Log\Driver\Monolog\StderrDriver::class  (extends MonologDriver)
        | - Rebet\Log\Driver\Monolog\TestDriver::class    (extends MonologDriver)
        | - and also you can use any log driver that implemented Psr\Log\LoggerInterface.
        */
        'channels' => [
            /*
            |--------------------------------------------------------------------------------------
            | WEB Channel
            |--------------------------------------------------------------------------------------
            | Logger setting for 'web' channel that default of web application using WebKernel.
            */
            'web' => [
                'driver' => [
                    '@factory' => StackDriver::class,
                    'channels' => ['app'],
                ],
            ],

            /*
            |--------------------------------------------------------------------------------------
            | API Channel
            |--------------------------------------------------------------------------------------
            | Logger setting for 'api' channel that default of API application using ApiKernel.
            */
            'api' => [
                'driver' => [
                    '@factory' => StackDriver::class,
                    'channels' => ['app'],
                ],
            ],

            /*
            |--------------------------------------------------------------------------------------
            | CLI Channel
            |--------------------------------------------------------------------------------------
            | Logger setting for 'cli' channel that default of Command Line Interface application
            | using CliKernel.
            */
            'cli' => [
                'driver' => [
                    '@factory' => StackDriver::class,
                    'channels' => ['command'],
                ],
            ],

            /*
            |--------------------------------------------------------------------------------------
            | App Channel
            |--------------------------------------------------------------------------------------
            | Logger setting for 'app' channel that default of using FileDriver.
            */
            'app' => [
                'driver' => [
                    '@factory'             => FileDriver::class,
                    'level'                => App::when([
                        'local'   => LogLevel::DEBUG,
                        'default' => LogLevel::ERROR,
                    ]),
                    'filename'             => App::path('/var/logs/app.log'),
                    'with_browser_console' => App::when([
                        'web@local' => true,
                        'default'   => false,
                    ]),
                    // --- You can change only what you need for these default options for FileDriver ---
                    // 'filename_format'      => '{filename}-{date}',
                    // 'filename_date_format' => 'Y-m-d',
                    // 'max_files'            => 0,
                    // 'file_permission'      => 0644,
                    // 'use_locking'          => false,
                    // 'format'               => null,
                    // 'stringifiers'         => [],
                    // 'bubble'               => true,
                    // '@setup'               => function($driver){ return $driver; }
                ],
            ],

            /*
            |--------------------------------------------------------------------------------------
            | Command Channel
            |--------------------------------------------------------------------------------------
            | Logger setting for 'command' channel that default of using FileDriver.
            */
            'command' => [
                'driver' => [
                    '@factory' => FileDriver::class,
                    'level'    => App::when([
                        'local'   => LogLevel::DEBUG,
                        'default' => LogLevel::INFO,
                    ]),
                    'filename' => App::path('/var/logs/command.log'),
                ],
            ],

            /*
            |--------------------------------------------------------------------------------------
            | Standard Error Channel
            |--------------------------------------------------------------------------------------
            | Logger setting for 'stderr' channel that default of using StderrDriver.
            | Normally you don't need to change this setting.
            */
            // 'stderr' => [
            //     'driver' => [
            //         '@factory' => StderrDriver::class,
            //         'level'    => LogLevel::DEBUG,
            //     ],
            // ],

            /*
            |--------------------------------------------------------------------------------------
            | Test Channel
            |--------------------------------------------------------------------------------------
            | Logger setting for 'test' channel that default of using TestDriver.
            | Normally you don't need to change this setting.
            */
            // 'test' => [
            //     'driver' => [
            //         '@factory' => TestDriver::class,
            //         'level'    => LogLevel::DEBUG,
            //     ],
            // ],
        ],

        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Fallback Logger
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | The fallback log stream if logger cannot be available.
        | Normally you don't need to change this setting.
        */
        // 'fallback_log' => defined('STDERR') ? STDERR : 'php://stderr',
    ],

    /*
    |==============================================================================================
    | Monolog Driver Configuration
    |==============================================================================================
    | This section defines Monolog Driver settings.
    | You may change these defaults as required.
    */
    MonologDriver::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Default Applied Processors
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | This setting is default applied processors for MonologDriver (and sub classes).
        | If you want to add extra information to log message, you can use '{extra.xxxx}' placeholder 
        | that suplieded information from XxxxProcessor implemented Monolog\Processor\ProcessorInterface.
        | And you can also use all of processors in Monolog\Processor package.
        | 
        | Provided Processors:
        | - {extra.git.[branch|commit])}                  : Monolog\Processor\GitProcessor::class
        | - {extra.hostname}                              : Monolog\Processor\HostnameProcessor::class
        | - {extra.[file|line|class|function]}            : Monolog\Processor\IntrospectionProcessor::class
        | - {extra.memory_peak_usage}                     : Monolog\Processor\MemoryPeakUsageProcessor::class
        | - {extra.memory_usage}                          : Monolog\Processor\MemoryUsageProcessor::class
        | - {extra.hg.[branch|revision]}                  : Monolog\Processor\MercurialProcessor::class
        | - {extra.process_id}                            : Monolog\Processor\ProcessIdProcessor::class        (enabled in library)
        | - {extra.tags}                                  : Monolog\Processor\TagProcessor::class
        | - {extra.uid}                                   : Monolog\Processor\UidProcessor::class
        | - {extra.[url|ip|http_method|server|referrer|]} : Monolog\Processor\WebProcessor::class
        */
        // 'processors=' => [
        //     ProcessIdProcessor::class,
        // ],
    ],

    /*
    |==============================================================================================
    | Log Message Text Formatter Configuration
    |==============================================================================================
    | This section defines text formatter configuration for log message.
    | You may change these defaults as required.
    */
    TextFormatter::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Default Message Format
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | This setting is default message format.
        | You can use extra information, if you applied XxxxProcessor to `MonologDriver.processors`.
        | (See `MonologDriver.processors` comments for more detailed information about 'extra.xxxx')
        |
        | In default, '{extra}' placeholder replaced by extra information text that stringified by 
        | function of 'stringifiers.{extra}'.
        | And other placeholders also.
        */
        // 'default_format' => "{datetime} {channel}/{extra.process_id} [{level_name}] {message}{context}{extra}{exception}\n",

        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Default Placeholder Stringifier Function
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | This setting is default stringifier function for replace log message placeholders.
        | It applies this function if each placeholder's stringifier are not defined in 
        | `TextFormatter.stringifiers`. 
        */
        // 'default_stringifier' => function ($val, array $masks, string $masked_label) { return Strings::stringify($val, $masks, $masked_label); },

        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Each Placeholder Stringifier Functions
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | These setting are stringifier functions for replacing placeholders in log message.
        */
        'stringifiers' => [
            /*
            |--------------------------------------------------------------------------------------
            | Stringifier Function For '{datetime}' Placeholder
            |--------------------------------------------------------------------------------------
            | In default, it convert to 'Y-m-d H:i:s.u' format text.
            */
            // '{datetime}' => function ($val, array $masks, string $masked_label) { return $val->format('Y-m-d H:i:s.u'); },

            /*
            |--------------------------------------------------------------------------------------
            | Stringifier Function For '{context}' Placeholder
            |--------------------------------------------------------------------------------------
            | In default, it convert from object/array to string using Strings::stringify().
            */
            // '{context}' => function ($val, array $masks, string $masked_label) { return empty($val) ? '' : "\n====== [  CONTEXT  ] ======\n".Strings::indent(Strings::stringify($val, $masks, $masked_label), "== ") ; },

            /*
            |--------------------------------------------------------------------------------------
            | Stringifier Function For '{extra}' Placeholder
            |--------------------------------------------------------------------------------------
            | In default, it convert from array to string using Strings::stringify().
            */
            // '{extra}' => function ($val, array $masks, string $masked_label) { return empty($val) ? '' : "\n------ [   EXTRA   ] ------\n".Strings::indent(Strings::stringify($val, $masks, $masked_label), "-- ") ; },

            /*
            |--------------------------------------------------------------------------------------
            | Stringifier Function For '{exception}' Placeholder
            |--------------------------------------------------------------------------------------
            | In default, it convert from exception to stack trace.
            */
            // '{exception}' => function ($val, array $masks, string $masked_label) { return empty($val) ? '' : "\n****** [ EXCEPTION ] ******\n".Strings::indent("{$val}", "** ") ; },
        ],

        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Property Names That Need Masking
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | If you want to be masking some properties like 'password' in context/extra on log message, 
        | you can do that by these properties write here.
        */
        'masks' => ['password', 'password_confirm'],

        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Masked Label
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Text label when masked.
        */
        // 'masked_label' => '********',
    ],
];
