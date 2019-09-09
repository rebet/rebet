<?php
namespace Rebet\Tests;

use Rebet\Common\Arrays;
use Rebet\Config\Config;
use Rebet\Database\Dao;
use Rebet\Database\Database;
use Rebet\Database\Driver\PdoDriver;
use Rebet\Database\Pagination\Cursor;

/**
 * Rebet Database Test Case Class
 *
 * We define various helper methods to reduce the labor of testing.
 */
abstract class RebetDatabaseTestCase extends RebetTestCase
{
    protected static $main   = null;
    protected static $sqlite = null;

    protected function setUp() : void
    {
        parent::setUp();
        if (self::$main == null) {
            self::$main = new PdoDriver('sqlite::memory:');
        }
        if (self::$sqlite == null) {
            self::$sqlite = new PdoDriver('sqlite::memory:');
        }
        Config::application([
            Dao::class => [
                'dbs' => [
                    'main'   => [
                        'driver'   => self::$main,
                        'dsn'      => 'sqlite::memory:',
                        // 'emulated_sql_log' => false,
                        // 'debug'            => true,
                    ],

                    'sqlite' => [
                        'driver'   => self::$sqlite,
                        'dsn'      => 'sqlite::memory:',
                        // 'emulated_sql_log' => false,
                        // 'debug'            => true,
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
                        // 'emulated_sql_log' => false,
                        // 'debug'            => true,
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
                        // 'emulated_sql_log' => false,
                        // 'debug'            => true,
                    ],
                ]
            ],
            Database::class => [
                'log_handler' => function (string $db_name, string $sql, array $params = []) {
                    echo("[{$db_name}] {$sql}\n");
                    if (!empty($param)) {
                        echo(Strings::indent("[PARAM]\n".Strings::stringify($params)."\n", '-- '));
                    }
                },
            ],
        ]);

        Dao::clear();

        foreach (array_keys(Dao::config('dbs')) as $db_name) {
            if (!($db = $this->connect($db_name, false))) {
                continue;
            }

            $db->begin();
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
            $db->commit();
        }

        Dao::clear();
        Cursor::clear();
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
            if (!($db = $this->connect($db_name, false))) {
                continue;
            }

            $tables = $this->tables($db_name);
            foreach ($tables as $table_name => $dml) {
                $db->execute("DROP TABLE IF EXISTS {$table_name}");
            }
        }
    }

    /**
     * @param string|null $db
     * @param boolean $mark_test_skiped
     * @return Database|null
     */
    protected function connect(?string $db = null, bool $mark_test_skiped = true) : ?Database
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

    protected function eachDb(\Closure $test, string ...$dbs)
    {
        $dbs    = empty($dbs) ? array_keys(Dao::config('dbs')) : $dbs ;
        $skiped = [];
        foreach ($dbs as $name) {
            $db = $this->connect($name, false);
            if ($db === null) {
                $skiped[] = $name;
                continue;
            }
            $test($db);
        }
        if (!empty($skiped)) {
            $this->markTestSkipped("Database ".implode(", ", $skiped)." was not ready.");
        }
    }
}
