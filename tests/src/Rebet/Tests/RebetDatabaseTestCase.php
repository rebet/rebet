<?php
namespace Rebet\Tests;

use Rebet\Database\Dao;
use Rebet\Database\Database;
use Rebet\Database\Pagination\Cursor;
use Rebet\Tools\Config\Config;
use Rebet\Tools\Utility\Arrays;
use Rebet\Tools\Utility\Strings;

/**
 * Rebet Database Test Case Class
 *
 * We define various helper methods to reduce the labor of testing.
 */
abstract class RebetDatabaseTestCase extends RebetTestCase
{
    public static function setUpBeforeClass() : void
    {
        parent::setUpBeforeClass();
    }

    public static function tearDownAfterClass() : void
    {
        foreach (array_keys(Dao::config('dbs')) as $db_name) {
            Dao::db($db_name)->close();
        }
        parent::tearDownAfterClass();
    }

    private $executed_sqls   = [];

    protected function setUp() : void
    {
        parent::setUp();
        Config::application([
            Dao::class => [
                'default_db' => 'sqlite',
                'dbs='       => [
                    'sqlite' => [
                        'dsn'   => 'sqlite:/tmp/sqlite/rebet.db',
                        // 'emulated_sql_log' => false,
                        'debug' => true,
                    ],

                    'mysql' => [
                        'dsn'      => 'mysql:host=mysql;dbname=rebet;charset=utf8mb4',
                        'user'     => 'rebet',
                        'password' => 'rebet',
                        'options'  => [
                            \PDO::ATTR_AUTOCOMMIT => false,
                        ],
                        // 'emulated_sql_log' => false,
                        'debug'    => true,
                    ],

                    'pgsql' => [
                        'dsn'      => "pgsql:host=pgsql;dbname=rebet;options='--client_encoding=UTF8'",
                        'user'     => 'rebet',
                        'password' => 'rebet',
                        'options'  => [],
                        // 'emulated_sql_log' => false,
                        'debug'    => true,
                    ],
                ]
            ],
            Database::class => [
                'log_handler' => function (string $db_name, string $sql, array $params = []) {
                    // echo("[{$db_name}] {$sql}\n");
                    // if (!empty($param)) {
                    //     echo(Strings::indent("[PARAM]\n".Strings::stringify($params)."\n", '-- '));
                    // }
                    $this->executed_sqls[] = $sql;
                },
            ],
        ]);

        Dao::clear();
        foreach (array_keys(Dao::config('dbs')) as $db_name) {
            if (!($db = $this->connect($db_name, false))) {
                continue;
            }

            $db->begin();
            foreach (['users', 'remember_tokens', 'banks', 'articles', 'groups', 'group_user', 'fortunes'] as $table_name) {
                $db->truncate($table_name, false);
                $records    = $this->records($db_name, $table_name);
                $table_name = $db->quoteIdentifier($table_name);
                foreach ($records as $record) {
                    if (Arrays::isSequential($record)) {
                        $db->execute("INSERT INTO {$table_name} VALUES (:values)", ['values' => $record]);
                    } else {
                        $db->execute("INSERT INTO {$table_name} (". join(',', array_map(function ($v) use ($db) { return $db->quoteIdentifier($v); }, array_keys($record))).") VALUES (:values)", ['values' => $record]);
                    }
                }
            }
            $db->commit();
        }
        Dao::clear();

        Cursor::clear();
        $this->executed_sqls = [];
    }

    protected function clearExecutedSqls() : void
    {
        $this->executed_sqls = [];
    }

    protected function executedSqls() : array
    {
        $sqls = $this->executed_sqls;
        $this->clearExecutedSqls();
        return $sqls;
    }

    protected function tables(string $db_name) : array
    {
        return [];
    }

    protected function records(string $db_name, string $table_name) : array
    {
        return [];
    }

    protected function tearDown() : void
    {
        foreach (array_keys(Dao::config('dbs')) as $db_name) {
            if (!($db = $this->connect($db_name, false))) {
                continue;
            }

            $tables = $this->tables($db_name);
            foreach ($tables as $table_name => $dml) {
                $db->truncate($table_name, false);
            }
        }
        parent::tearDown();
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
                $this->markTestSkipped("Database '$db' was not ready: {$e->getMessage()}.\n".$e->getTraceAsString());
            }
        }
        return null;
    }

    protected function eachDb(\Closure $test, string ...$dbs)
    {
        $dbs    = empty($dbs) ? array_keys(Dao::config('dbs')) : $dbs ;
        $skiped = [];
        foreach ($dbs as $name) {
            $db = $this->connect($name);
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
