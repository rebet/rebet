<?php

use Rebet\Database\Compiler\BuiltinCompiler;
use Rebet\Database\Dao;
use Rebet\Database\Database;
use Rebet\Database\Pagination\Cursor;
use Rebet\Database\Pagination\Pager;
use Rebet\Database\Ransack\BuiltinRansacker;
use Rebet\Database\Ransack\Ransack;

return [
    Dao::class => [
        'dbs' => [
            'main' => [
                'dsn'              => null,
                'user'             => null,
                'password'         => null,
                'options'          => [],
                'debug'            => false,
                'emulated_sql_log' => true,
                'log_handler'      => null,
            ],
        ],
        'default_db' => 'main',
    ],

    Database::class => [
        'compiler'    => BuiltinCompiler::class,
        'ransacker'   => BuiltinRansacker::class,
        'log_handler' => null, // function(string $db_name, string $sql, array $params = []) {}
    ],

    Cursor::class => [
        'storage'  => null,
        'lifetime' => '1h',
    ],

    Pager::class => [
        'page_name'          => 'page',
        'page_size_name'     => 'page_size',
        'default_page_size'  => 10,
        'default_each_side'  => 0,
        'default_need_total' => false,
        'resolver'           => null,   // function(Pager $pager) : Pager { ... }
    ],

    Ransack::class => [
        'compound_separator' => '/[\s　]/',
        'value_converters'   => [
            'ignore'   => function ($value) { return null; },
            'contains' => function ($value) { return '%'.str_replace(['|', '%', '_'], ['||', '|%', '|_'], $value).'%'; },
            'starts'   => function ($value) { return     str_replace(['|', '%', '_'], ['||', '|%', '|_'], $value).'%'; },
            'ends'     => function ($value) { return '%'.str_replace(['|', '%', '_'], ['||', '|%', '|_'], $value)    ; },
        ],
        'predicates'         => [
            // predicate => [template, value_converter, multiple_columns_conjunction]
            'eq'           => ["{col} = {val}"                           , null      , 'OR' ],
            'not_eq'       => ["{col} <> {val}"                          , null      , 'AND'],
            'in'           => ["{col} IN ({val})"                        , null      , 'OR' ],
            'not_in'       => ["{col} NOT IN ({val})"                    , null      , 'AND'],
            'gt'           => ["{col} > {val}"                           , null      , 'OR' ],
            'lt'           => ["{col} < {val}"                           , null      , 'OR' ],
            'gteq'         => ["{col} >= {val}"                          , null      , 'OR' ],
            'lteq'         => ["{col} <= {val}"                          , null      , 'OR' ],
            'after'        => ["{col} > {val}"                           , null      , 'OR' ],
            'before'       => ["{col} < {val}"                           , null      , 'OR' ],
            'from'         => ["{col} >= {val}"                          , null      , 'OR' ],
            'to'           => ["{col} <= {val}"                          , null      , 'OR' ],
            'contains'     => ["{col} LIKE {val} ESCAPE '|'"             , 'contains', 'OR' ],
            'not_contains' => ["{col} NOT LIKE {val} ESCAPE '|'"         , 'contains', 'AND'],
            'starts'       => ["{col} LIKE {val} ESCAPE '|'"             , 'starts'  , 'OR' ],
            'not_starts'   => ["{col} NOT LIKE {val} ESCAPE '|'"         , 'starts'  , 'AND'],
            'ends'         => ["{col} LIKE {val} ESCAPE '|'"             , 'ends'    , 'OR' ],
            'not_ends'     => ["{col} NOT LIKE {val} ESCAPE '|'"         , 'ends'    , 'AND'],
            'null'         => ["{col} IS NULL"                           , 'ignore'  , 'AND'],
            'not_null'     => ["{col} IS NOT NULL"                       , 'ignore'  , 'OR' ],
            'blank'        => ["({col} IS NULL OR {col} = '')"           , 'ignore'  , 'AND'],
            'not_blank'    => ["({col} IS NOT NULL AND {col} <> '')"     , 'ignore'  , 'OR' ],
        ],
    ],
];
