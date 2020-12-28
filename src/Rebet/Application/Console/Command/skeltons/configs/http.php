<?php

use Rebet\Http\Cookie\Cookie;
use Rebet\Http\HttpStatus;
use Rebet\Http\Request;
use Rebet\Http\Session\Session;
use Rebet\Http\Session\Storage\Handler\MemcachedSessionHandler;
use Rebet\Http\Session\Storage\Handler\MongoDbSessionHandler;
use Rebet\Http\Session\Storage\Handler\NativeFileSessionHandler;
use Rebet\Http\Session\Storage\Handler\RedisSessionHandler;
use Rebet\Http\Session\Storage\SessionStorage;

return [
    HttpStatus::class => [
        'reason_phrases' => [
            100 => 'Continue',
            101 => 'Switching Protocols',
            102 => 'Processing',
            103 => 'Early Hints',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            207 => 'Multi-Status',
            208 => 'Already Reported',
            226 => 'IM Used',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            307 => 'Temporary Redirect',
            308 => 'Permanent Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Payload Too Large',
            414 => 'URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Range Not Satisfiable',
            417 => 'Expectation Failed',
            418 => "I'm a teapot",
            421 => 'Misdirected Request',
            422 => 'Unprocessable Entity',
            423 => 'Locked',
            424 => 'Failed Dependency',
            426 => 'Upgrade Required',
            451 => 'Unavailable For Legal Reasons',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            506 => 'Variant Also Negotiates',
            507 => 'Insufficient Storage',
            508 => 'Loop Detected',
            509 => 'Bandwidth Limit Exceeded',
            510 => 'Not Extended',
            511 => 'Network Authentication Required',
        ],
    ],

    Cookie::class => [
        'expire'    => 0,
        'path'      => function ($path) { return (Request::current() ? Request::current()->getRoutePrefix() : '').$path; },
        'domain'    => null,
        'secure'    => false,
        'http_only' => true,
        'raw'       => false,
        'samesite'  => self::SAMESITE_LAX,
    ],

    Session::class => [
        'storage' => SessionStorage::class,
    ],

    SessionStorage::class => [
        'handler' => null,
        'options' => [
            'cache_expire'             => null,
            'cache_limiter'            => null,
            'cookie_domain'            => null,
            'cookie_httponly'          => null,
            'cookie_lifetime'          => null,
            'cookie_path'              => null,
            'cookie_secure'            => null,
            'gc_divisor'               => null,
            'gc_maxlifetime'           => null,
            'gc_probability'           => null,
            'lazy_write'               => null,
            'name'                     => null,
            'referer_check'            => null,
            'serialize_handler'        => null,
            'use_strict_mode'          => null,
            'use_cookies'              => null,
            'use_only_cookies'         => null,
            'use_trans_sid'            => null,
            'upload_progress.enabled'  => null,
            'upload_progress.cleanup'  => null,
            'upload_progress.prefix'   => null,
            'upload_progress.name'     => null,
            'upload_progress.freq'     => null,
            'upload_progress.min_freq' => null,
            'url_rewriter.tags'        => null,
            'sid_length'               => null,
            'sid_bits_per_character'   => null,
            'trans_sid_hosts'          => null,
            'trans_sid_tags'           => null,
        ],
    ],

    MemcachedSessionHandler::class => [
        'expiretime' => 86400,
        'prefix'     => 'rebet',
    ],

    MongoDbSessionHandler::class => [
        'database'     => null,
        'collection'   => null,
        'id_field'     => '_id',
        'data_field'   => 'data',
        'time_field'   => 'time',
        'expiry_field' => 'expires_at',
    ],

    NativeFileSessionHandler::class => [
        'save_path' => ini_get('session.save_path'),
    ],

    RedisSessionHandler::class => [
        'prefix' => 'rebet',
    ],
];
