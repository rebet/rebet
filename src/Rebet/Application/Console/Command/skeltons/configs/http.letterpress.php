<?php

use Rebet\Application\App;
use Rebet\Http\Cookie\Cookie;
use Rebet\Http\HttpStatus;
use Rebet\Http\Request;
use Rebet\Http\Session\Session;
use Rebet\Http\Session\Storage\Handler\MemcachedSessionHandler;
use Rebet\Http\Session\Storage\Handler\MongoDbSessionHandler;
use Rebet\Http\Session\Storage\Handler\NativeFileSessionHandler;
use Rebet\Http\Session\Storage\Handler\RedisSessionHandler;
use Rebet\Http\Session\Storage\SessionStorage;

/*
|##################################################################################################
| Http Package Configurations
|##################################################################################################
| This file defines configuration for classes in Rebet\Http package.
|
| The Rebet configuration file provides multiple ways to describe environment-dependent settings.
| You can use these methods when set environment-dependent settings.
|
| 1. Use `Env::promise('KEY')` to get value from `.env` file for each environment.
| 2. Use `App::when(['env' => value, ...])` to switch value by channel and environment.
| 3. Use `http@{env}.php` file to override environment dependency value of `http.php`
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
    | Cookie Configuration
    |==============================================================================================
    | This section defines Cookie settings.
    | You may change these defaults as required.
    */
    Cookie::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Default Time of Cookie Expires
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Here you may set default time of cookie expires.
        */
        'expire' => 0,

        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Default Cookie Available Path
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Here you may set default path on the server in which the cookie will be available on.
        | By default, the path that takes into account the root context path is automatically
        | selected, so usually does not need to be changed.
        */
        // 'path' => fn($path) => (Request::current() ? Request::current()->getRoutePrefix() : '').$path,

        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Default Cookie Available Domain
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Here you may set default domain that the cookie is available to.
        | Normally you don't need to change the settings, but if you need to set the domain.
        */
        'domain' => null,

        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Default Cookie Secure
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Here you may set defaults for secure  (use cookie only over HTTPS or not).
        | Whether the client should send back the cookie only over HTTPS or null to auto-enable
        | this when the request is already using HTTPS.
        */
        'secure' => true,

        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Default HTTP Access Only
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Setting this value to true will prevent JavaScript from accessing the value of the cookie
        | and the cookie will only be accessible through the HTTP protocol.
        | You are free to modify this option if needed.
        */
        'http_only' => true,

        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Default No URL Encoding
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Setting this value to true will the cookie value should be sent with no url encoding.
        | Normally you don't need to change the settings, but you can set to true if you want.
        */
        'raw' => false,

        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Default Same-Site Cookies
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | This option determines how your cookies behave when cross-site requests take place, and
        | can be used to mitigate CSRF attacks. By default, we will set this value to "lax" since
        | this is a secure default value.
        |
        | Supported Options:
        |  - Cookie::SAMESITE_*
        */
        'samesite' => Cookie::SAMESITE_LAX,
    ],


    /*
    |==============================================================================================
    | Session Configuration
    |==============================================================================================
    | This section defines Session settings.
    | You may change these defaults as required.
    */
    Session::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Session Storage
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Normally you don't need to change this setting, but you can optionally configure a custom
        | storage that implements Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface.
        |
        | Provided Storages:
        |  - Rebet\Http\Session\Storage\ArraySessionStorage
        |  - Rebet\Http\Session\Storage\SessionStorage      (default)
        */
        // 'storage' => SessionStorage::class,
    ],


    /*
    |==============================================================================================
    | Session Storage Configuration
    |==============================================================================================
    | This section defines Session Storage settings that use by default.
    | You may change these defaults as required.
    */
    SessionStorage::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Session Storage Handler
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Here you may set session storage handler you want.
        | Defaultly handler is set NativeFileSessionHandler (based on Symfony's one).
        | You can use some handlers that based on Symfony provided.
        |
        | Provided Handlers:
        |  - Rebet\Http\Session\Storage\Handler\DatabaseSessionHandler
        |  - Rebet\Http\Session\Storage\Handler\MemcachedSessionHandler
        |  - Rebet\Http\Session\Storage\Handler\MigratingSessionHandler
        |  - Rebet\Http\Session\Storage\Handler\MongoDbSessionHandler
        |  - Rebet\Http\Session\Storage\Handler\NativeFileSessionHandler
        |  - Rebet\Http\Session\Storage\Handler\NullSessionHandler
        |  - Rebet\Http\Session\Storage\Handler\RedisSessionHandler
        |  - Rebet\Http\Session\Storage\Handler\StrictSessionHandler
        |  - And you can use any handler that extends \SessionHandler or implements
        |    \SessionHandlerInterface and \SessionUpdateTimestampHandlerInterface
        */
        'handler' => NativeFileSessionHandler::class,

        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Session Storage Options
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Here you may set session storage options you want.
        | Depending on how you want the storage handler to behave you probably want to override
        | these options.
        |
        | "auto_start", is not supported as it tells PHP to start a session before PHP starts to
        | execute user-land code. Setting during runtime has no effect.
        */
        'options' => [
            // --- List of options with their defaults ---
            // @see https://php.net/session.configuration for options, but we omit 'session.' from
            // the beginning of the keys for convenience.
            // -------------------------------------------
            // 'cache_expire'             => "0",
            // 'cache_limiter'            => "",  // use "0" to prevent headers from being sent entirely.
            // 'cookie_domain'            => "",
            // 'cookie_httponly'          => "",
            // 'cookie_lifetime'          => "0",
            // 'cookie_path'              => "/",
            // 'cookie_secure'            => "",
            // 'cookie_samesite'          => null,
            // 'gc_divisor'               => "100",
            // 'gc_maxlifetime'           => "1440",
            // 'gc_probability'           => "1",
            // 'lazy_write'               => "1",
            // 'name'                     => "PHPSESSID",
            // 'referer_check'            => "",
            // 'serialize_handler'        => "php",
            // 'use_strict_mode'          => "0",
            // 'use_cookies'              => "1",
            // 'use_only_cookies'         => "1",
            // 'use_trans_sid'            => "0",
            // 'upload_progress.enabled'  => "1",
            // 'upload_progress.cleanup'  => "1",
            // 'upload_progress.prefix'   => "upload_progress_",
            // 'upload_progress.name'     => "PHP_SESSION_UPLOAD_PROGRESS",
            // 'upload_progress.freq'     => "1%",
            // 'upload_progress.min_freq' => "1",
            // 'url_rewriter.tags'        => "a=href,area=href,frame=src,form=,fieldset=",
            // 'sid_length'               => "32",
            // 'sid_bits_per_character'   => "5",
            // 'trans_sid_hosts'          => $_SERVER['HTTP_HOST'],
            // 'trans_sid_tags'           => "a=href,area=href,frame=src,form=",
        ],
    ],


    /*
    |==============================================================================================
    | Native File Session Handler Configuration
    |==============================================================================================
    | This section defines Native File Session Handler settings.
    | You may change these defaults as required.
    */
    NativeFileSessionHandler::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Session Save Path
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Here you may set session save path you want.
        */
        // 'save_path' => ini_get('session.save_path'),
    ],


    /*
    |==============================================================================================
    | Mongo DB Session Handler Configuration
    |==============================================================================================
    | This section defines Mongo DB Session Handler settings.
    | If you use this handler you need to set the required options, and also you may change these
    | defaults as required.
    |
    | Available Options:
    |  * database     : The name of the database                        [required]
    |  * collection   : The name of the collection                      [required]
    |  * id_field     : The field name for storing the session id       [default: _id]
    |  * data_field   : The field name for storing the session data     [default: data]
    |  * time_field   : The field name for storing the timestamp        [default: time]
    |  * expiry_field : The field name for storing the expiry-timestamp [default: expires_at]
    |
    | It is strongly recommended to put an index on the `expiry_field` for garbage-collection.
    | Alternatively it's possible to automatically expire the sessions in the database as described
    | below:
    |
    | A TTL collections can be used on MongoDB 2.2+ to cleanup expired sessions automatically.
    | Such an index can for example look like this:
    |
    |     db.<session-collection>.createIndex(
    |         { "<expiry-field>": 1 },
    |         { "expireAfterSeconds": 0 }
    |     )
    |
    | More details on: https://docs.mongodb.org/manual/tutorial/expire-data/
    |
    | If you use such an index, you can drop `gc_probability` to 0 since no garbage-collection is
    | required.
    */
    MongoDbSessionHandler::class => [
        'database'     => null,
        'collection'   => null,
        // --- You can change only what you need for these default options ---
        // 'id_field'     => '_id',
        // 'data_field'   => 'data',
        // 'time_field'   => 'time',
        // 'expiry_field' => 'expires_at',
    ],


    /*
    |==============================================================================================
    | Memcached Session Handler Configuration
    |==============================================================================================
    | This section defines Memcached Session Handler settings.
    | If you use this handler you need to set the required options, and also you may change these
    | defaults as required.
    |
    | Available Options:
    |  * prefix     : The prefix to use for the memcached keys in order to avoid collision. [required]
    |  * expiretime : The time to live in seconds.                                          [default: 86400]
    */
    MemcachedSessionHandler::class => [
        'prefix'     => App::codeName(),
        // --- You can change only what you need for these default options ---
        // 'expiretime' => 86400,
    ],


    /*
    |==============================================================================================
    | Redis Session Handler Configuration
    |==============================================================================================
    | This section defines Redis Session Handler settings.
    | If you use this handler you need to set the required options, and also you may change these
    | defaults as required.
    |
    | Available Options:
    |  * prefix : The prefix to use for the memcached keys in order to avoid collision. [required]
    |  * ttl    : The time to live in seconds.                                          [default: null]
    */
    RedisSessionHandler::class => [
        'prefix' => App::codeName(),
        // --- You can change only what you need for these default options ---
        // 'ttl'    => null,
    ],


    /*
    |==============================================================================================
    | HTTP Status Configuration
    |==============================================================================================
    | This section defines HTTP Status settings.
    | You may change these defaults as required.
    */
    HttpStatus::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | HTTP Status Reason Phrases
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Here you can define and customize HTTP status reason phrases if you want.
        | Normally you don't need to change this setting.
        |
        | @see Rebet\Http\HttpStatus::defaultConfig() for all of default definitions.
        */
        // 'reason_phrases' => [
        //     // ex)
        //     301 => 'Moved Permanently',
        //     404 => 'Not Found',
        //     500 => 'Internal Server Error',
        // ],
    ],
];
