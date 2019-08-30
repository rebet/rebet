<?php
namespace Rebet\Tests;

use Exception;
use Rebet\Common\Arrays;
use Rebet\Config\Config;
use Rebet\Database\Dao;
use Rebet\Database\Database;
use Rebet\Database\Driver\PdoDriver;
use Rebet\Database\Pagination\Pager;

/**
 * Rebet Database Test Case Class
 *
 * We define various helper methods to reduce the labor of testing.
 */
abstract class RebetDatabaseTestCase extends RebetTestCase
{
    protected static $sqlite = null;

    protected function setUp() : void
    {
        parent::setUp();
        if (self::$sqlite == null) {
            self::$sqlite = new PdoDriver('sqlite::memory:');
        }
        Config::application([
            Dao::class => [
                'dbs' => [
                    'sqlite' => [
                        'driver'   => self::$sqlite,
                        'dsn'      => 'sqlite::memory:',
                        // 'log_handler' => function ($name, $sql, $params =[]) { echo $sql; },
                        // 'emulated_sql_log' => false,
                    ],

                    // CREATE DATABASE rebet_test DEFAULT CHARACTER SET utf8mb4 DEFAULT COLLATE utf8mb4_bin;
                    'mysql' => [
                        'driver'   => PdoDriver::class,
                        'dsn'      => 'mysql:host=localhost;dbname=rebet_test;charset=utf8mb4',
                        'user'     => 'root',
                        'password' => '',
                        'options'  => [
                            \PDO::ATTR_AUTOCOMMIT => false,
                        ],
                        // 'log_handler' => function ($name, $sql, $params =[]) { echo $sql; },
                        // 'emulated_sql_log' => false,
                    ],

                    // CREATE DATABASE rebet_test WITH OWNER = postgres ENCODING = 'UTF8' CONNECTION LIMIT = -1;
                    // pg_hba.conf:
                    //   host    all     postgres             127.0.0.1/32            trust
                    //   host    all     postgres             ::1/128                 trust
                    'pgsql' => [
                        'driver'   => PdoDriver::class,
                        'dsn'      => "pgsql:host=localhost;dbname=rebet_test;options='--client_encoding=UTF8'",
                        'user'     => 'postgres',
                        'password' => '',
                        'options'  => [],
                        // 'log_handler' => function ($name, $sql, $params =[]) { echo $sql; },
                        // 'emulated_sql_log' => false,
                    ],
                ]
            ],
            Pager::class => [
                'resolver' => function (Pager $pager) { return $pager; }
            ]
        ]);

        foreach (array_keys(Dao::config('dbs')) as $db_name) {
            try {
                $db = Dao::db($db_name);
            } catch (Exception $e) {
                // Skip not ready
                continue;
            }

            $tables = $this->tables($db_name);
            foreach ($tables as $table_name => $dml) {
                $db->execute("DROP TABLE IF EXISTS {$table_name}");
                $db->execute($dml);

                $records = $this->records($db_name, $table_name);
                foreach ($records as $record) {
                    if (Arrays::isSequential($record)) {
                        $db->execute("INSERT INTO {$table_name} VALUES (:values)", ['values' => $record]);
                    } else {
                        $db->execute("INSERT INTO {$table_name} (". join(',', array_keys($record)).") VALUES (:values)", ['values' => $record]);
                    }
                }
            }
        }
    }

    protected function tables(string $db_name) : array
    {
        return [];
    }

    protected function records(string $db_name, string $table_name) : array
    {
        return [];
    }

    protected function tearDown()
    {
        foreach (array_keys(Dao::config('dbs')) as $db_name) {
            try {
                $db = Dao::db($db_name);
            } catch (Exception $e) {
                // Skip not ready
                continue;
            }

            $tables = $this->tables($db_name);
            foreach ($tables as $table_name => $dml) {
                $db->execute("DROP TABLE IF EXISTS {$table_name}");
            }
        }
    }

    protected function connect(string $db, bool $mark_test_skiped = false) : ?Database
    {
        try {
            return Dao::db($db);
        } catch (\Exception $e) {
            if ($mark_test_skiped) {
                $this->markTestSkipped("Database '$db' was not ready.");
            }
        }
        return null;
    }
}
