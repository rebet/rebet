<?php

use Rebet\Application\App;
use Rebet\Application\Database\Pagination\Storage\SessionCursorStorage;
use Rebet\Database\Dao;
use Rebet\Database\Database;
use Rebet\Database\Driver\MysqlDriver;
use Rebet\Database\Driver\PgsqlDriver;
use Rebet\Database\Driver\SqliteDriver;
use Rebet\Database\Driver\SqlsrvDriver;
use Rebet\Database\Pagination\Cursor;
use Rebet\Database\Pagination\Pager;
use Rebet\Database\Query;
use Rebet\Database\Ransack\Ransack;
use Rebet\Log\Log;
use Rebet\Tools\Config\Config;
use Rebet\Tools\Utility\Env;

/*
|##################################################################################################
| Database Package Configurations
|##################################################################################################
| This file defines configuration for classes in Rebet\Database package.
|
| The Rebet configuration file provides multiple ways to describe environment-dependent settings.
| You can use these methods when set environment-dependent settings.
|
| 1. Use `Env::promise('KEY')` to get value from `.env` file for each environment.
| 2. Use `App::when(['env' => value, ...])` to switch value by channel and environment.
| 3. Use `database@{env}.php` file to override environment dependency value of `database.php`
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
    | DAO Configuration
    |==============================================================================================
    | This section defines DAO (Database Access Object) settings.
    | You may change these defaults as required.
    */
    Dao::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Default Database Connections Name
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | This option controls the default database connection that gets used while using this
        | database library. This connection is used when another is not explicitly specified when
        | executing a given Dao function.
        */
        //{%-- commentif !$use_db, '// ', '--- Please uncomment if you want to use database ---' -%}
        'default_db' => '{! $database !}',
        //{%-- endcommentif -%}


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Database Connections
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Here are each of the database connections setup for your application.
        | You may even define multiple database connections for same/another databse connection.
        |
        | All database work in Rebet is done through the PHP PDO facilities so make sure you have
        | the driver for your particular database of choice installed on your machine before you
        | begin development.
        |
        | Below is a supplement to some common options.
        |  - driver      : Defaultly use driver of DSN protocol name, but you can change the driver
        |                  to use here.
        |  - options     : The default option definitions are different for each driver, and these
        |                  can be found by looking at the defaultConfig() for each driver class.
        |  - log_handler : Defaultly use log handler defined by `Database.log_handler`, but you can
        |                  change the log handler to use just for this database connection.
        */
        'dbs' => [
            //{%-- if $database == 'sqlite' -%}
            /*
            |--------------------------------------------------------------------------------------
            | SQLite 3 Connection
            |--------------------------------------------------------------------------------------
            | A databse connection for SQLite 3.
            */
            //{%-- commentif !$use_db, '// ', '--- Please uncomment if you want to use database ---' -%}
            'sqlite' => [
                'dsn' => App::when([
                    'local'      => 'sqlite:/tmp/sqlite/{! $db_name !}.db', // actual: {app_root}/docker/var/data/sqlite/{! $db_name !}.db
                    'production' => 'sqlite:'.App::path('/var/data/sqlite/{! $db_name !}.db'),
                ]),
                'debug' => App::when([
                    'local'      => true,
                    'production' => false,
                ]),
                // --- You can change only what you need for these default options ---
                // 'driver'   => 'sqlite',
                // 'options=' => [
                //     'pdo' => [
                //         \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                //         \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                //         \PDO::ATTR_EMULATE_PREPARES   => false,
                //     ],
                //     'statement' => [
                //         \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                //     ],
                // ],
                // 'log_handler' => function (Database $db, Query $query) { Log::debug("[".$db->name()."] SQL: ".$query->emulate()); },
            ],
            //{%-- endcommentif -%}
            //{%-- endif -%}
            //{%-- if $database == 'mysql' -%}
            /*
            |--------------------------------------------------------------------------------------
            | MySQL Connection
            |--------------------------------------------------------------------------------------
            | A databse connection for MySQL.
            */
            //{%-- commentif !$use_db, '// ', '--- Please uncomment if you want to use database ---' -%}
            'mysql' => [
                'dsn' => App::when([
                    'local'      => 'mysql:host=mysql;dbname={! $db_name !};charset=utf8mb4',
                    'production' => 'mysql:host=localhost;dbname={! $db_name !};charset=utf8mb4',
                ]),
                'user'     => Env::promise('DB_USERNAME'),
                'password' => Env::promise('DB_PASSWORD'),
                'debug'    => App::when([
                    'local'      => true,
                    'production' => false,
                ]),
                // --- You can change only what you need for these default options ---
                // 'driver'   => 'mysql',
                // 'options=' => [
                //     'pdo' => [
                //         \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                //         \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                //         \PDO::ATTR_EMULATE_PREPARES   => false,
                //         \PDO::ATTR_AUTOCOMMIT         => false,
                //     ],
                //     'statement' => [
                //         \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                //     ],
                // ],
                // 'log_handler' => function (Database $db, Query $query) { Log::debug("[".$db->name()."] SQL: ".$query->emulate()); },
            ],
            //{%-- endcommentif -%}
            //{%-- endif -%}
            //{%-- if $database == 'mariadb' -%}
            /*
            |--------------------------------------------------------------------------------------
            | MariaDB Connection
            |--------------------------------------------------------------------------------------
            | A databse connection for MariaDB.
            */
            //{%-- commentif !$use_db, '// ', '--- Please uncomment if you want to use database ---' -%}
            'mariadb' => [
                'dsn' => App::when([
                    'local'      => 'mysql:host=mariadb;dbname={! $db_name !};charset=utf8mb4',
                    'production' => 'mysql:host=localhost;dbname={! $db_name !};charset=utf8mb4',
                ]),
                'user'     => Env::promise('DB_USERNAME'),
                'password' => Env::promise('DB_PASSWORD'),
                'debug'    => App::when([
                    'local'      => true,
                    'production' => false,
                ]),
                // --- You can change only what you need for these default options ---
                // 'driver'   => 'mysql',
                // 'options=' => [
                //     'pdo' => [
                //         \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                //         \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                //         \PDO::ATTR_EMULATE_PREPARES   => false,
                //         \PDO::ATTR_AUTOCOMMIT         => false,
                //     ],
                //     'statement' => [
                //         \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                //     ],
                // ],
                // 'log_handler' => function (Database $db, Query $query) { Log::debug("[".$db->name()."] SQL: ".$query->emulate()); },
            ],
            //{%-- endcommentif -%}
            //{%-- endif -%}
            //{%-- if $database == 'pgsql' -%}
            /*
            |--------------------------------------------------------------------------------------
            | PostgreSQL Connection
            |--------------------------------------------------------------------------------------
            | A databse connection for PostgreSQL.
            */
            //{%-- commentif !$use_db, '// ', '--- Please uncomment if you want to use database ---' -%}
            'pgsql' => [
                'dsn' => App::when([
                    'local'      => "pgsql:host=pgsql;dbname={! $db_name !};options='--client_encoding=UTF8'",
                    'production' => "pgsql:host=localhost;dbname={! $db_name !};options='--client_encoding=UTF8'",
                ]),
                'user'     => Env::promise('DB_USERNAME'),
                'password' => Env::promise('DB_PASSWORD'),
                'debug'    => App::when([
                    'local'      => true,
                    'production' => false,
                ]),
                // --- You can change only what you need for these default options ---
                // 'driver'   => 'pgsql',
                // 'options=' => [
                //     'pdo' => [
                //         \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                //         \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                //         \PDO::ATTR_EMULATE_PREPARES   => false,
                //     ],
                //     'statement' => [
                //         \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                //     ],
                // ],
                // 'log_handler' => function (Database $db, Query $query) { Log::debug("[".$db->name()."] SQL: ".$query->emulate()); },
            ],
            //{%-- endcommentif -%}
            //{%-- endif -%}
            //{%-- if $database == 'sqlsrv' -%}
            /*
            |--------------------------------------------------------------------------------------
            | Microsoft SQL Server Connection
            |--------------------------------------------------------------------------------------
            | A databse connection for Microsoft SQL Server.
            */
            //{%-- commentif !$use_db, '// ', '--- Please uncomment if you want to use database ---' -%}
            'sqlsrv' => [
                'dsn' => App::when([
                    'local'      => 'sqlsrv:server=sqlsrv;database={! $db_name !}',
                    'production' => 'sqlsrv:server=localhost;database={! $db_name !}',
                ]),
                'user'     => Env::promise('DB_USERNAME'),
                'password' => Env::promise('DB_PASSWORD'),
                'debug'    => App::when([
                    'local'      => true,
                    'production' => false,
                ]),
                // --- You can change only what you need for these default options ---
                // 'driver'   => 'sqlsrv',
                // 'options=' => [
                //     'pdo' => [
                //         \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                //         \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                //     ],
                //     'statement' => [
                //         \PDO::ATTR_EMULATE_PREPARES   => false,
                //     ],
                // ],
                // 'log_handler' => function (Database $db, Query $query) { Log::debug("[".$db->name()."] SQL: ".$query->emulate()); },
            ],
            //{%-- endcommentif -%}
            //{%-- endif -%}
        ],


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Database Drivers
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Here you may define all of the databse "drivers" for your application.
        | Normally, it is not necessary to add/change the setting, but if necessary, you can
        | add/change a new/enhanced database driver that implemented Rebet\Database\Driver\Driver.
        |
        | Preinstalled Drivers:
        |  - 'sqlite' => Rebet\Database\Driver\SqliteDriver::class
        |  - 'mysql'  => Rebet\Database\Driver\MysqlDriver::class
        |  - 'pgsql'  => Rebet\Database\Driver\PgsqlDriver::class
        |  - 'sqlsrv' => Rebet\Database\Driver\SqlsrvDriver::class
        */
        'drivers' => [
            // 'driver_name' => YourDatabaseDriver::class,
        ],
    ],


    /*
    |==============================================================================================
    | Database Configuration
    |==============================================================================================
    | This section defines database settings.
    | You may change these defaults as required.
    */
    Database::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | SQL Compiler
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Here you may define SQL "compiler" for your application.
        | Normally, it is not necessary to change the setting, but if necessary, you can use a
        | new/enhanced compiler that implemented Rebet\Database\Compiler\Compiler.
        |
        | Preinstalled Compiler:
        |  - Rebet\Database\Compiler\BuiltinCompiler::class
        */
        // 'compiler' => YourCompiler::class,


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Ransacker for Ransack Search
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Here you may define "ransacker" for ransack search that use in your application.
        |
        | The `Ransack Search` is an extended search function that makes it easy to create search
        | forms. It is feature that influenced by activerecord-hackery/ransack for Ruby.
        | Rebet's `Ransack Search` concept is much similar to that of Ruby, but there are
        | differences in predicate keywords and features provided.
        |
        | Normally, it is not necessary to change the setting, but if necessary, you can use a
        | new/enhanced ransacker that implemented Rebet\Database\Ransack\Ransacker.
        | And also you can easily enhanced ransack search function by Ransack and each DatabaseDriver
        | configuration setttings (you can find it at the bottom of this file).
        |
        | Preinstalled Ransacker:
        |  - Rebet\Database\Ransack\BuiltinRansacker::class
        */
        // 'ransacker' => YourRansacker::class,


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | SQL Log Handler
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Here you may define default SQL "log_handler" for your application.
        | The log handler defined here can be overwritten individually in configuration of
        | `Dao.dbs.{db_name}.log_handler`.
        */
        'log_handler' => function (Database $db, Query $query) { Log::debug("[".$db->name()."] SQL: ".$query->emulate()); },
    ],


    /*
    |==============================================================================================
    | Database Pager Configuration
    |==============================================================================================
    | This section defines database pager settings.
    | Settings related to paging across multiple packages are aggregated in `App.paginate`
    | configuration for convenience when setting up.
    | Please update `application.php` to modify the setting values that refered here.
    */
    Pager::class => Config::refer(App::class, 'paginate'),


    /*
    |==============================================================================================
    | Database Pagination Cursor Configuration
    |==============================================================================================
    | This section defines database pagination cursor settings.
    | The cursor is a pointer of first item of current page that is used to achieve high-speed
    | paging without using offset.
    | You may change these defaults as required.
    */
    Cursor::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Cursor Storage
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Here you may define coursor data "storage" for your application.
        | Normally, it is not necessary to change the setting, but if necessary, you can use a
        | new/enhanced storage that implemented Rebet\Database\Pagination\Storage\CursorStorage.
        |
        | Supported:
        |  - @see Rebet\Database\Pagination\Storage\ArrayCursorStorage
        |  - @see Rebet\Application\Database\Pagination\Storage\SessionCursorStorage
        */
        'storage' => SessionCursorStorage::class,

        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Cursor Lifetime
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | This is cursor data lifetime.
        | Set a value that you think is appropriate.
        |
        | Even if the expiration date expires and the cursor data is lost, the slow page feed
        | processing using offset will be executed instead, so the page processing will not become
        | impossible.
        */
        'lifetime' => '1h',
    ],


    /*
    |==============================================================================================
    | Ransack Search Configuration [for All Kind of Databases]
    |==============================================================================================
    | This section defines ransack search configuration settings for all kind of databases.
    | Normally, it is not necessary to change the setting, but you can add/change the ransack
    | search behavior you want.
    |
    | See Rebet\Database\Ransack\Ransack's class comments for a detailed specification and examples
    | of ransack search.
    |
    | NOTE: Items that depend on the database specifications should be set on each driver side.
    */
    Ransack::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Compound Separator
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | This is compound separator regex for separating search word.
        | Defaultly set this configuration to white spaces expressed in '/[\s　]/'.
        | If you want, change the settings as needed.
        */
        // 'compound_separator' => '/[\s　]/',


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Value Converters
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | There are value converters for ransack search.
        | Sometimes value conversion processing is required to perform a search in SQL.
        | A typical example of this would be a LIKE search.
        | Here, we define a converter that converts the values required for performing a ransack search.
        | If you want, add/change the settings as needed.
        |
        | Preinstalled Converters:
        |  - Items commented out below
        */
        'value_converters' => [
            // --- You can add/change only what you need for these default converters ---
            // 'ignore'   => function ($value) { return null; },
            // 'contains' => function ($value) { return '%'.str_replace(['|', '%', '_'], ['||', '|%', '|_'], $value).'%'; },
            // 'starts'   => function ($value) { return     str_replace(['|', '%', '_'], ['||', '|%', '|_'], $value).'%'; },
            // 'ends'     => function ($value) { return '%'.str_replace(['|', '%', '_'], ['||', '|%', '|_'], $value)    ; },
        ],


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Ransack Predicates
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | There are predicates for ransack search.
        | If you want, add/change the settings as needed.
        |
        | The format of the settings is as follows.
        |
        |  - 'predicate_name' => ['sql_template', 'value_converter_name', 'multiple_columns_conjunction'],
        |
        | You can use two placeholders in your SQL template, {col} and {val}. These are expanded to
        | column names (ex. name) and placeholders for values (ex. :name), respectively. If you need
        | to convert the value, specify the defined 'value_converter' name to 'value_converter_name',
        | otherwise set null. At the end, set 'AND' or 'OR' to 'multiple_columns_conjunction'. This
        | logical condition is used to combine the conditions for each column when the target of
        | 'predicates' is alias and it is divided into multiple columns.
        |
        | Preinstalled Rredicates:
        |  - Items commented out below
        */
        'predicates' => [
            // --- You can add/change only what you need for these default converters ---
            // 'eq'           => ["{col} = {val}"                           , null      , 'OR' ],
            // 'not_eq'       => ["{col} <> {val}"                          , null      , 'AND'],
            // 'in'           => ["{col} IN ({val})"                        , null      , 'OR' ],
            // 'not_in'       => ["{col} NOT IN ({val})"                    , null      , 'AND'],
            // 'gt'           => ["{col} > {val}"                           , null      , 'OR' ],
            // 'lt'           => ["{col} < {val}"                           , null      , 'OR' ],
            // 'gteq'         => ["{col} >= {val}"                          , null      , 'OR' ],
            // 'lteq'         => ["{col} <= {val}"                          , null      , 'OR' ],
            // 'after'        => ["{col} > {val}"                           , null      , 'OR' ],
            // 'before'       => ["{col} < {val}"                           , null      , 'OR' ],
            // 'from'         => ["{col} >= {val}"                          , null      , 'OR' ],
            // 'to'           => ["{col} <= {val}"                          , null      , 'OR' ],
            // 'contains'     => ["{col} LIKE {val} ESCAPE '|'"             , 'contains', 'OR' ],
            // 'not_contains' => ["{col} NOT LIKE {val} ESCAPE '|'"         , 'contains', 'AND'],
            // 'starts'       => ["{col} LIKE {val} ESCAPE '|'"             , 'starts'  , 'OR' ],
            // 'not_starts'   => ["{col} NOT LIKE {val} ESCAPE '|'"         , 'starts'  , 'AND'],
            // 'ends'         => ["{col} LIKE {val} ESCAPE '|'"             , 'ends'    , 'OR' ],
            // 'not_ends'     => ["{col} NOT LIKE {val} ESCAPE '|'"         , 'ends'    , 'AND'],
            // 'null'         => ["{col} IS NULL"                           , 'ignore'  , 'AND'],
            // 'not_null'     => ["{col} IS NOT NULL"                       , 'ignore'  , 'OR' ],
            // 'blank'        => ["({col} IS NULL OR {col} = '')"           , 'ignore'  , 'AND'],
            // 'not_blank'    => ["({col} IS NOT NULL AND {col} <> '')"     , 'ignore'  , 'OR' ],
        ],
    ],


    //{%-- if $database == 'sqlite' -%}
    /*
    |==============================================================================================
    | Databse Driver Configuration [for SQLite 3]
    |==============================================================================================
    | This section defines database driver configuration settings for SQLite 3.
    | Normally, it is not necessary to change the setting, but you can add/change the default
    | options and datbase dependent ransack search behavior you want.
    |
    | See Rebet\Database\Driver\SqliteDriver's class comments for a detailed specification and
    | examples of ransack search.
    */
    //{%-- commentif !$use_db, '// ', '--- Please uncomment if you want to use database ---' -%}
    SqliteDriver::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Default PDO Options
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Normally, it is not necessary to change the setting, but you can change the default PDO
        | options you want.
        |
        | Preinstalled PDO Options:
        |  - Items commented out below
        */
        // 'options=' => [
        //     'pdo' => [
        //         \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
        //         \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        //         \PDO::ATTR_EMULATE_PREPARES   => false,
        //     ],
        //     'statement' => [
        //         \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        //     ],
        // ],


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Ransack Search Configuration [for SQLite 3]
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Normally, it is not necessary to change the setting, but you can add/change the ransack
        | search behavior you want.
        */
        'ransack' => [
            /*
            |--------------------------------------------------------------------------------------
            | Dependent Value Converters
            |--------------------------------------------------------------------------------------
            | Here, we define a database dependent converter that converts the values required for
            | performing a ransack search.
            | You can also override the common value converter defined in `Ransack.value_converters`
            | here.
            */
            'value_converters' => [
                // You can add/override only what you need
            ],


            /*
            |--------------------------------------------------------------------------------------
            | Dependent Ransack Predicates
            |--------------------------------------------------------------------------------------
            | Here, we define a database dependent ransack predicates.
            | The format is the same as `Ransack.predicates`.
            | You can also override the common behavior defined in `Ransack.predicates` here.
            |
            | Preinstalled Rredicates:
            |  - Items commented out below
            */
            'predicates' => [
                // --- You can add/change/override only what you need for these default converters ---
                // 'matches'     => ["{col} REGEXP {val}"     , null , 'OR' ],
                // 'not_matches' => ["{col} NOT REGEXP {val}" , null , 'AND'],
                // 'search'      => ["{col} MATCH {val}"      , null , 'OR' ],
            ],


            /*
            |--------------------------------------------------------------------------------------
            | Ransack Options
            |--------------------------------------------------------------------------------------
            | Here, we define a database dependent ransack options.
            |
            | We sometimes want to convert column values when doing a search in SQL. Typical
            | examples of this are date part extraction, age calculation, and ambiguous search by
            | changing collate in some DBs. Ransack options support the conversion process for
            | these column values, which can be used in combination with Ransack predicates. In
            | other words, this allows you to search for'birthday_lt_age' or'payment_at_eq_y'.
            |
            | Preinstalled Ransack Options:
            |  - Items commented out below
            */
            'options' => [
                // --- You can add/change only what you need for these ransack options ---
                // 'bin' => 'BINARY {col}',
                // 'ci'  => '{col} COLLATE nocase',
                // 'len' => 'LENGTH({col})',
                // 'uc'  => 'UPPER({col})',
                // 'lc'  => 'LOWER({col})',
                // 'age' => "CAST((STRFTIME('%Y%m%d', 'now') - STRFTIME('%Y%m%d', {col})) / 10000 AS int)",
                // 'y'   => "STRFTIME('%Y', {col})",
                // 'm'   => "STRFTIME('%m', {col})",
                // 'd'   => "STRFTIME('%d', {col})",
                // 'h'   => "STRFTIME('%H', {col})",
                // 'i'   => "STRFTIME('%M', {col})",
                // 's'   => "STRFTIME('%S', {col})",
                // 'dow' => "STRFTIME('%w', {col})",
            ],
        ],
    ],
    //{%-- endcommentif -%}
    //{%-- endif -%}
    //{%-- if in_array($database, ['mysql', 'mariadb'], true) -%}
    /*
    |==============================================================================================
    | Databse Driver Configuration [for MySQL/MariaDB]
    |==============================================================================================
    | This section defines database driver configuration settings for MySQL/MariaDB.
    | Normally, it is not necessary to change the setting, but you can add/change the default
    | options and datbase dependent ransack search behavior you want.
    |
    | See Rebet\Database\Driver\MysqlDriver's class comments for a detailed specification and
    | examples of ransack search.
    */
    //{%-- commentif !$use_db, '// ', '--- Please uncomment if you want to use database ---' -%}
    MysqlDriver::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Default PDO Options
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Normally, it is not necessary to change the setting, but you can change the default PDO
        | options you want.
        |
        | Preinstalled PDO Options:
        |  - Items commented out below
        */
        // 'options=' => [
        //     'pdo' => [
        //         \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
        //         \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        //         \PDO::ATTR_EMULATE_PREPARES   => false,
        //         \PDO::ATTR_AUTOCOMMIT         => false,
        //     ],
        //     'statement' => [
        //         \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        //     ],
        // ],


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Ransack Search Configuration [for MySQL/MariaDB]
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Normally, it is not necessary to change the setting, but you can add/change the ransack
        | search behavior you want.
        */
        'ransack' => [
            /*
            |--------------------------------------------------------------------------------------
            | Dependent Value Converters
            |--------------------------------------------------------------------------------------
            | Here, we define a database dependent converter that converts the values required for
            | performing a ransack search.
            | You can also override the common value converter defined in `Ransack.value_converters`
            | here.
            */
            'value_converters' => [
                // You can add/override only what you need
            ],


            /*
            |--------------------------------------------------------------------------------------
            | Dependent Ransack Predicates
            |--------------------------------------------------------------------------------------
            | Here, we define a database dependent ransack predicates.
            | The format is the same as `Ransack.predicates`.
            | You can also override the common behavior defined in `Ransack.predicates` here.
            |
            | Preinstalled Rredicates:
            |  - Items commented out below
            */
            'predicates' => [
                // --- You can add/change/override only what you need for these default converters ---
                // 'matches'     => ["{col} REGEXP {val}"          , null , 'OR' ],
                // 'not_matches' => ["{col} NOT REGEXP {val}"      , null , 'AND'],
                // 'search'      => ["MATCH({col}) AGAINST({val})" , null , 'OR' ],
            ],


            /*
            |--------------------------------------------------------------------------------------
            | Ransack Options
            |--------------------------------------------------------------------------------------
            | Here, we define a database dependent ransack options.
            |
            | We sometimes want to convert column values when doing a search in SQL. Typical
            | examples of this are date part extraction, age calculation, and ambiguous search by
            | changing collate in some DBs. Ransack options support the conversion process for
            | these column values, which can be used in combination with Ransack predicates. In
            | other words, this allows you to search for'birthday_lt_age' or'payment_at_eq_y'.
            |
            | Preinstalled Ransack Options:
            |  - Items commented out below
            */
            'options' => [
                // --- You can add/change only what you need for these ransack options ---
                // 'bin' => 'BINARY {col}',
                // 'cs'  => '{col} COLLATE utf8mb4_bin',
                // 'ci'  => '{col} COLLATE utf8mb4_general_ci',
                // 'fs'  => '{col} COLLATE utf8mb4_unicode_ci',
                // 'len' => 'CHAR_LENGTH({col})',
                // 'uc'  => 'UPPER({col})',
                // 'lc'  => 'LOWER({col})',
                // 'age' => 'TIMESTAMPDIFF(YEAR, {col}, CURRENT_DATE)',
                // 'y'   => 'YEAR({col})',
                // 'm'   => 'MONTH({col})',
                // 'd'   => 'DAY({col})',
                // 'h'   => 'HOUR({col})',
                // 'i'   => 'MINUTE({col})',
                // 's'   => 'SECOND({col})',
                // 'dow' => 'DAYOFWEEK({col})',
            ],
        ],
    ],
    //{%-- endcommentif -%}
    //{%-- endif -%}
    //{%-- if $database == 'pgsql' -%}
    /*
    |==============================================================================================
    | Databse Driver Configuration [for PostgreSQL]
    |==============================================================================================
    | This section defines database driver configuration settings for PostgreSQL.
    | Normally, it is not necessary to change the setting, but you can add/change the default
    | options and datbase dependent ransack search behavior you want.
    |
    | See Rebet\Database\Driver\PgsqlDriver's class comments for a detailed specification and
    | examples of ransack search.
    */
    //{%-- commentif !$use_db, '// ', '--- Please uncomment if you want to use database ---' -%}
    PgsqlDriver::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Default PDO Options
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Normally, it is not necessary to change the setting, but you can change the default PDO
        | options you want.
        |
        | Preinstalled PDO Options:
        |  - Items commented out below
        */
        // 'options=' => [
        //     'pdo' => [
        //         \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
        //         \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        //         \PDO::ATTR_EMULATE_PREPARES   => false,
        //     ],
        //     'statement' => [
        //         \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        //     ],
        // ],


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Ransack Search Configuration [for PostgreSQL]
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Normally, it is not necessary to change the setting, but you can add/change the ransack
        | search behavior you want.
        */
        'ransack' => [
            /*
            |--------------------------------------------------------------------------------------
            | Dependent Value Converters
            |--------------------------------------------------------------------------------------
            | Here, we define a database dependent converter that converts the values required for
            | performing a ransack search.
            | You can also override the common value converter defined in `Ransack.value_converters`
            | here.
            */
            'value_converters' => [
                // You can add/override only what you need
            ],


            /*
            |--------------------------------------------------------------------------------------
            | Dependent Ransack Predicates
            |--------------------------------------------------------------------------------------
            | Here, we define a database dependent ransack predicates.
            | The format is the same as `Ransack.predicates`.
            | You can also override the common behavior defined in `Ransack.predicates` here.
            |
            | Preinstalled Rredicates:
            |  - Items commented out below
            */
            'predicates' => [
                // --- You can add/change/override only what you need for these default converters ---
                // 'matches'     => ["{col} ~ {val}"                           , null , 'OR' ],
                // 'not_matches' => ["{col} !~ {val}"                          , null , 'AND'],
                // 'search'      => ["to_tsvector({col}) @@ to_tsquery({val})" , null , 'OR' ],
            ],


            /*
            |--------------------------------------------------------------------------------------
            | Ransack Options
            |--------------------------------------------------------------------------------------
            | Here, we define a database dependent ransack options.
            |
            | We sometimes want to convert column values when doing a search in SQL. Typical
            | examples of this are date part extraction, age calculation, and ambiguous search by
            | changing collate in some DBs. Ransack options support the conversion process for
            | these column values, which can be used in combination with Ransack predicates. In
            | other words, this allows you to search for'birthday_lt_age' or'payment_at_eq_y'.
            |
            | Preinstalled Ransack Options:
            |  - Items commented out below
            */
            'options' => [
                // --- You can add/change only what you need for these ransack options ---
                // 'len' => 'LENGTH({col})',
                // 'uc'  => 'UPPER({col})',
                // 'lc'  => 'LOWER({col})',
                // 'age' => "DATE_PART('year', AGE({col}))",
                // 'y'   => "DATE_PART('year', {col})",
                // 'm'   => "DATE_PART('month', {col})",
                // 'd'   => "DATE_PART('day', {col})",
                // 'h'   => "DATE_PART('hour', {col})",
                // 'i'   => "DATE_PART('minute', {col})",
                // 's'   => "DATE_PART('second', {col})",
                // 'dow' => "DATE_PART('dow', {col})",
            ],
        ],
    ],
    //{%-- endcommentif -%}
    //{%-- endif -%}
    //{%-- if $database == 'sqlsrv' -%}
    /*
    |==============================================================================================
    | Databse Driver Configuration [for Microsoft SQL Server]
    |==============================================================================================
    | This section defines database driver configuration settings for Microsoft SQL Server.
    | Normally, it is not necessary to change the setting, but you can add/change the default
    | options and datbase dependent ransack search behavior you want.
    |
    | See Rebet\Database\Driver\SqlsrvDriver's class comments for a detailed specification and
    | examples of ransack search.
    */
    //{%-- commentif !$use_db, '// ', '--- Please uncomment if you want to use database ---' -%}
    SqlsrvDriver::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Default PDO Options
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Normally, it is not necessary to change the setting, but you can change the default PDO
        | options you want.
        |
        | Preinstalled PDO Options:
        |  - Items commented out below
        */
        // 'options=' => [
        //     'pdo' => [
        //         \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
        //         \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        //     ],
        //     'statement' => [
        //         \PDO::ATTR_EMULATE_PREPARES   => false,
        //     ],
        // ],


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Ransack Search Configuration [for Microsoft SQL Server]
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Normally, it is not necessary to change the setting, but you can add/change the ransack
        | search behavior you want.
        */
        'ransack' => [
            /*
            |--------------------------------------------------------------------------------------
            | Dependent Value Converters
            |--------------------------------------------------------------------------------------
            | Here, we define a database dependent converter that converts the values required for
            | performing a ransack search.
            | You can also override the common value converter defined in `Ransack.value_converters`
            | here.
            */
            'value_converters' => [
                // You can add/override only what you need
            ],


            /*
            |--------------------------------------------------------------------------------------
            | Dependent Ransack Predicates
            |--------------------------------------------------------------------------------------
            | Here, we define a database dependent ransack predicates.
            | The format is the same as `Ransack.predicates`.
            | You can also override the common behavior defined in `Ransack.predicates` here.
            |
            | Preinstalled Rredicates:
            |  - Items commented out below
            */
            'predicates' => [
                // --- You can add/change/override only what you need for these default converters ---
                // 'search'  => ["CONTAINS({col}, {val})" , null , 'OR' ],
                // 'meaning' => ["FREETEXT({col}, {val})" , null , 'OR' ],
            ],


            /*
            |--------------------------------------------------------------------------------------
            | Ransack Options
            |--------------------------------------------------------------------------------------
            | Here, we define a database dependent ransack options.
            |
            | We sometimes want to convert column values when doing a search in SQL. Typical
            | examples of this are date part extraction, age calculation, and ambiguous search by
            | changing collate in some DBs. Ransack options support the conversion process for
            | these column values, which can be used in combination with Ransack predicates. In
            | other words, this allows you to search for'birthday_lt_age' or'payment_at_eq_y'.
            |
            | Preinstalled Ransack Options:
            |  - Items commented out below
            */
            'options' => [
                // --- You can add/change only what you need for these ransack options ---
                // 'len' => 'LEN({col})',
                // 'uc'  => 'UPPER({col})',
                // 'lc'  => 'LOWER({col})',
                // 'age' => "DATEDIFF(yy, {col}, GETDATE()) - IIF(GETDATE() >= DATEADD(yy, DATEDIFF(yy, {col}, GETDATE()), {col}), 0, 1)",
                // 'y'   => 'YEAR({col})',
                // 'm'   => 'MONTH({col})',
                // 'd'   => 'DAY({col})',
                // 'h'   => 'DATEPART(hh, {col})',
                // 'i'   => 'DATEPART(mi, {col})',
                // 's'   => 'DATEPART(ss, {col})',
                // 'dow' => 'DATEPART(dw, {col})',
            ],
        ],
    ],
    //{%-- endcommentif -%}
    //{%-- endif -%}
];
