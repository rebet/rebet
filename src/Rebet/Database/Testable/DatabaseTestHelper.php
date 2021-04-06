<?php
namespace Rebet\Database\Testable;

use Rebet\Database\Dao;
use Rebet\Database\Database;
use Rebet\Database\Query;
use Rebet\Tools\Config\Config;
use Rebet\Tools\Utility\Json;
use Rebet\Tools\Utility\Strings;

/**
 * Database Test Helper Trait
 * 
 * The assertion methods are declared static and can be invoked from any context, for instance, 
 * using static::assert*() or $this->assert*() in a class that use TestHelper.
 *
 * It expect this trait to be used in below,
 *  - Class that extended PHPUnit\Framework\TestCase(actual PHPUnit\Framework\Assert) class.
 *  - Class that used Rebet\Tools\Testable\TestHelper trait.
 * 
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
trait DatabaseTestHelper
{
    /**
     * @var Query[] executed SQL queries
     */
    private static $executed_queries = [];

    /**
     * Set up database configuration for unit testing.
     * This method is expected to be called in setUp().
     */
    public static function setUpDatabase() : void
    {
        Config::runtime([
            Database::class => [
                'log_handler' => function (Database $db, Query $query) {
                    static::$executed_queries[] = $query ;
                },
            ],
        ]);
        static::$executed_queries = [];
    }

    /**
     * Tear down database configuration for unit testing.
     * This method is expected to be called in tearDownAfterClass().
     */
    public static function tearDownDatabase() : void
    {
        static::eachDb(function(Database $db){
            $db->close();
        });
        Dao::clear();
    }
    
    /**
     * Apply tests to all or given defined databases.
     *
     * @param \Closure $test function(Database $db, string $driver) { ... }
     * @param string ...$dbs that are test targets
     * @return void
     */
    public static function eachDb(\Closure $test, string ...$dbs) : void
    {
        $dbs = empty($dbs) ? array_keys(Dao::config('dbs')) : $dbs ;
        foreach ($dbs as $name) {
            $db = Dao::db($name);
            $test($db, $db->driverName());
        }
    }

    /**
     * Setup dataset by given data.
     * This method is expected to be called in setUp() or each test method.
     * The data format is 
     * [
     *    'table_name' => [
     *        ['col1', 'col2', ...], // header columns section
     *        [1     , 'foo' , ...], // data section
     *        [2     , 'bar' , ...],
     *        ...
     *    ],
     *    'table_name_2' => null,    // define value as null or [] if you want to just truncate table ($with_truncate = true)
     * ]
     *
     * @param array $data for insert into tables
     * @param bool $with_truncate (default: true)
     * @return void
     */
    public static function setUpDataSet(array $data, bool $with_truncate = true) : void
    {
        Dao::clear();
        static::eachDb(function(Database $db) use ($data, $with_truncate) {
            $db->begin();
            foreach (array_keys($data) as $table_name) {
                if($with_truncate) {
                    $db->truncate($table_name, false);
                }
                $records    = $data[$table_name] ?? [];
                $columns    = array_shift($records) ?? [];
                $table_name = $db->driver()->quoteIdentifier($table_name);
                foreach ($records as $record) {
                    $db->execute("INSERT INTO {$table_name} (". join(',', array_map(function ($v) use ($db) { return $db->driver()->quoteIdentifier($v); }, $columns)).") VALUES (:values)", ['values' => $record]);
                }
            }
            $db->commit();
        });
        Dao::clear();
    }

    /**
     * Dump all executed queries.
     *
     * @param bool $emulate SQL or not (default: true)
     * @return void
     */
    public static function dumpExecutedQueries(bool $emulate = true) : void
    {
        echo "\n";
        echo "---------- [ Executed Queries ] ----------\n";
        foreach(static::$executed_queries as $i => $query) {
            echo "[".$query->driver()->name().": {$i}] >> ".($emulate ? $query->emulate() : $query->toString())."\n";
        }
        echo "------------------------------------------\n";
    }

    /**
     * Clear executed query.
     *
     * @return void
     */
    public static function clearExecutedQueries() : void
    {
        static::$executed_queries = [];
    }

    /**
     * Dequeue oldest executed query.
     *
     * @return Query|null
     */
    public static function dequeueExecutedQuery() : ?Query
    {
        return array_shift(static::$executed_queries);
    }

    // ========================================================================
    // Dependent PHPUnit\Framework\Assert assertions
    // ========================================================================

    /**
     * @see PHPUnit\Framework\Assert::fail
     */
    public abstract static function fail(string $message = ''): void;

    // ========================================================================
    // Dependent Rebet\Tools\Testable\TestHelper methods and assertions
    // ========================================================================

    /**
     * @see Rebet\Tools\Testable\TestHelper::success
     */
    public abstract static function success() : void ;

    /**
     * @see Rebet\Tools\Testable\TestHelper::assertStringWildcardAll
     */
    public abstract static function assertStringWildcardAll($expects, string $actual, array $wildcards = [], string $message = '') : void;

    // ========================================================================
    // Extended assertions
    // ========================================================================

    /**
     * Asserts that an executed SQL matches expected wildcards aliased ['*' => '@'].
     * This assertion checks emulated SQL but it will trim mark comment of '/⋆ Emulated SQL ⋆/ '.
     * So you can JUST check SQL like "SELECT * FROM table_name WHERE col = 'value'".
     *
     * @param Database $db
     * @param string $expect SQL for general drivers
     * @param array $depended_expects some drivers generate different SQL, you can define each expect SQL like ['driver_name' => 'Database dependent SQL']
     * @param array $wildcards (default: ['*' => '@'])
     * @param string $message (default: '')
     * @return void
     */
    public static function assertExecutedQueryWildcard(Database $db, string $expect, array $depended_expects = [], array $wildcards = ['*' => '@'], string $message = '') : void
    {
        static::assertStringWildcardAll(
            $depended_expects[$db->driverName()] ?? $expect,
            Strings::ltrim(static::dequeueExecutedQuery()->emulate(), "/* Emulated SQL */ ", 1),
            $wildcards,
            '['.$db->driverName().'] : '.$message
        );
    }

    /**
     * Asserts that database records matches.
     *
     * The expects data format is 
     * [
     *    'table_name' => [
     *        ['col1', 'col2', ...], // Header columns section
     *        [1     , 'foo' , ...], // Data section
     *        [2     , 'bar' , ...],
     *        ...
     *    ],
     *    'table_name_2' => null,    // Define value as null or [] if you want to check data is not exists.
     * ]
     * 
     * NOTE: Expect data MUST be included primary keys.
     * 
     * @param Database $db
     * @param array $expects data (MUST be included primary keys)
     * @param bool $strict if true then check rows count are same. (default: true)
     * @param string $message (default: '')
     * @return void
     */
    public static function assertDatabaseMatches(Database $db, array $expects, bool $strict = true, string $message = '') : void
    {
        $is_debug = $db->isDebug();
        $db->debug(false);
        $message  = empty($message) ? $message : "{$message}\n" ;
        foreach ($expects as $table_name => $rows) {
            $columns = array_shift($rows) ?? [];
            $table   = $db->driver()->quoteIdentifier($table_name);
            if($rows != array_unique($rows, SORT_REGULAR)) {
                static::fail("{$message}Failed asserting that table '{$table_name}' on {$db->name()} duplicate expect data were contains.");
            }
            if($strict) {
                $actual_count = $db->count("SELECT * FROM {$table}");
                $expect_count = count($rows);
                if($expect_count != $actual_count) {
                    static::fail(
                        "{$message}Failed asserting that table '{$table_name}' on {$db->name()} rows count: expect \"{$expect_count}\" but actual \"{$actual_count}\".\n".
                        "\n".
                        "---------- [ Full data of {$table_name} ] ----------\n".
                        Strings::stringify($db->select("SELECT * FROM {$table}", [reset($columns) => 'ASC']))."\n".
                        "----------------------------------------------------\n"
                    );
                }
            }
            foreach ($rows as $row) {
                $params = array_combine($columns, $row);
                $sql    = "SELECT * FROM {$table} WHERE 1=1";
                foreach($params as $column => $value) {
                    $sql .= " AND ".$db->driver()->quoteIdentifier($column).($value === null ? " IS NULL" : " = :{$column}") ;
                }
                if(($count = $db->count($sql, $params)) != 1) {
                    static::fail(
                        "{$message}Failed asserting that table '{$table_name}' on {$db->name()} rows ".($count === 0 ? "miss match" : "too many match").": expect \n".
                        Strings::indent(Strings::stringify($params), " ", 4)."\n".
                        "but SQL \n".
                        Strings::indent($db->sql($sql, $params)->emulate(), ">> ")."\n".
                        ($count === 0 ? "was not hit any data.\n" : "was hit {$count} data.\n").
                        "\n".
                        "---------- [ Full data of {$table_name} ] ----------\n".
                        Strings::stringify($db->select("SELECT * FROM {$table}", [reset($columns) => 'ASC']))."\n".
                        "----------------------------------------------------\n"
                    );
                }
            }
        }
        static::success();
        $db->debug($is_debug);
    }
}
