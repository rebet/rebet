<?php

use Rebet\Database\Analysis\BuiltinAnalyzer;
use Rebet\Database\Compiler\BuiltinCompiler;
use Rebet\Database\Converter\BuiltinConverter;
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
        'converter'   => BuiltinConverter::class,
        'analyzer'    => BuiltinAnalyzer::class,
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
            'common' => [
                'eq'           => ["{col} = {val}"                           , null      , 'OR' ],
                'not_eq'       => ["{col} <> {val}"                          , null      , 'AND'],
                'in'           => ["{col} IN ({val})"                        , null      , 'OR' ],
                'not_in'       => ["{col} NOT IN ({val})"                    , null      , 'AND'],
                'lt'           => ["{col} < {val}"                           , null      , 'OR' ],
                'lteq'         => ["{col} <= {val}"                          , null      , 'OR' ],
                'gteq'         => ["{col} >= {val}"                          , null      , 'OR' ],
                'gt'           => ["{col} > {val}"                           , null      , 'OR' ],
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
            'sqlite' => [
                'matches'      => ["{col} REGEXP {val}"                      , null      , 'OR' ],
                'not_matches'  => ["{col} NOT REGEXP {val}"                  , null      , 'AND'],
                'search'       => ["{col} MATCH {val}"                       , null      , 'OR' ],
            ],
            'mysql' => [
                'matches'      => ["{col} REGEXP {val}"                      , null      , 'OR' ],
                'not_matches'  => ["{col} NOT REGEXP {val}"                  , null      , 'AND'],
                'search'       => ["MATCH({col}) AGAINST({val})"             , null      , 'OR' ],
            ],
            'pgsql' => [
                'matches'      => ["{col} ~ {val}"                           , null      , 'OR' ],
                'not_matches'  => ["{col} !~ {val}"                          , null      , 'AND'],
                'search'       => ["to_tsvector({col}) @@ to_tsquery({val})" , null      , 'OR' ],
            ],
        ],
        'options' => [
            'sqlite' => [
                'bin' => 'BINARY {col}',
                'ci'  => '{col} COLLATE nocase',
                'len' => 'LENGTH({col})',
                'uc'  => 'UPPER({col})',
                'lc'  => 'LOWER({col})',
                'age' => "CAST((STRFTIME('%Y%m%d', 'now') - STRFTIME('%Y%m%d', {col})) / 10000 AS int)",
                'y'   => "STRFTIME('%Y', {col})",
                'm'   => "STRFTIME('%m', {col})",
                'd'   => "STRFTIME('%d', {col})",
                'h'   => "STRFTIME('%H', {col})",
                'i'   => "STRFTIME('%M', {col})",
                's'   => "STRFTIME('%S', {col})",
                'dow' => "STRFTIME('%w', {col})",
            ],
            'mysql' => [
                'bin' => 'BINARY {col}',
                'cs'  => '{col} COLLATE utf8mb4_bin',
                'ci'  => '{col} COLLATE utf8mb4_general_ci',
                'fs'  => '{col} COLLATE utf8mb4_unicode_ci',
                'len' => 'CHAR_LENGTH({col})',
                'uc'  => 'UPPER({col})',
                'lc'  => 'LOWER({col})',
                'age' => 'TIMESTAMPDIFF(YEAR, {col}, CURRENT_DATE)',
                'y'   => 'YEAR({col})',
                'm'   => 'MONTH({col})',
                'd'   => 'DAY({col})',
                'h'   => 'HOUR({col})',
                'i'   => 'MINUTE({col})',
                's'   => 'SECOND({col})',
                'dow' => 'DAYOFWEEK({col})',
            ],
            'pgsql' => [
                'len' => 'LENGTH({col})',
                'uc'  => 'UPPER({col})',
                'lc'  => 'LOWER({col})',
                'age' => "DATE_PART('year', AGE({col}))",
                'y'   => "DATE_PART('year', {col})",
                'm'   => "DATE_PART('month', {col})",
                'd'   => "DATE_PART('day', {col})",
                'h'   => "DATE_PART('hour', {col})",
                'i'   => "DATE_PART('minute', {col})",
                's'   => "DATE_PART('second', {col})",
                'dow' => "DATE_PART('dow', {col})",
            ],
        ],
    ],
];
