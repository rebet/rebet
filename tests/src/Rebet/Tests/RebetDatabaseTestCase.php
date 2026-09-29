<?php

declare(strict_types=1);

namespace Rebet\Tests;

use Override;
use Rebet\Database\Dao;
use Rebet\Database\Pagination\Cursor;
use Rebet\Database\Testable\DatabaseTestHelper;
use Rebet\Tools\Config\Config;

/**
 * Rebet Database Test Case Class
 *
 * We define various helper methods to reduce the labor of testing.
 */
abstract class RebetDatabaseTestCase extends RebetTestCase
{
    use DatabaseTestHelper;

    #[Override]
    public static function tearDownAfterClass(): void
    {
        static::tearDownDatabase();
        parent::tearDownAfterClass();
    }

    #[Override]
    protected static function setUpStatic(): void
    {
        parent::setUpStatic();
        Config::application([
            Dao::class => [
                'default_db' => 'sqlite',
                'dbs='       => [
                    'sqlite'  => [
                        'dsn'   => 'sqlite:/tmp/sqlite/rebet.db',
                        'debug' => true,
                    ],

                    'mysql'   => [
                        'dsn'      => 'mysql:host=mysql;dbname=rebet;charset=utf8mb4',
                        'user'     => 'rebet',
                        'password' => 'rebet',
                        'options'  => [],
                        'debug'    => true,
                    ],

                    'mariadb' => [
                        'dsn'      => 'mysql:host=mariadb;dbname=rebet;charset=utf8mb4',
                        'user'     => 'rebet',
                        'password' => 'rebet',
                        'options'  => [],
                        'debug'    => true,
                    ],

                    'pgsql'   => [
                        'dsn'      => "pgsql:host=pgsql;dbname=rebet;options='--client_encoding=UTF8'",
                        'user'     => 'rebet',
                        'password' => 'rebet',
                        'options'  => [],
                        'debug'    => true,
                    ],
                ],
            ],
        ]);

        Cursor::clear();
        static::setUpDatabase();
    }

    #[Override]
    protected function setUp(): void
    {
        self::setUpStatic();
    }
}
