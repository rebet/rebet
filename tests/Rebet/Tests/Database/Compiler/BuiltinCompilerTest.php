<?php
namespace Rebet\Tests\Database\Compiler;

use PDO;
use PHPUnit\DbUnit\DataSet\ArrayDataSet;
use Rebet\Config\Config;
use Rebet\Database\Compiler\BuiltinCompiler;
use Rebet\Database\Compiler\Compiler;
use Rebet\Database\Dao;
use Rebet\Database\Driver\PdoDriver;
use Rebet\Database\OrderBy;
use Rebet\Database\Pagination\Cursor;
use Rebet\Database\Pagination\Pager;
use Rebet\Database\PdoParameter;
use Rebet\DateTime\DateTime;
use Rebet\Tests\Mock\Enum\Gender;
use Rebet\Tests\RebetDatabaseTestCase;

class BuiltinCompilerTest extends RebetDatabaseTestCase
{
    /**
     * @var Compiler
     */
    private $compiler;

    protected function setUp() : void
    {
        parent::setUp();
        Config::application([
            Dao::class => [
                'dbs' => [
                    'sqlite' => [
                        'driver'   => self::$pdo,
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
                        // 'log_handler' => function ($name, $sql, $params =[]) { echo $sql; }
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
                        // 'log_handler' => function ($name, $sql, $params =[]) { echo $sql; }
                        'emulated_sql_log' => false,
                    ],
                ]
            ],
            Pager::class => [
                'resolver' => function (Pager $pager) { return $pager; }
            ]
        ]);

        $this->compiler = new BuiltinCompiler();
        DateTime::setTestNow('2001-02-03 04:05:06');
    }

    protected function getDataSet()
    {
        return new ArrayDataSet([
        ]);
    }

    public function dataCompiles() : array
    {
        $this->setUp();
        return [
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user",
                [],
                "SELECT * FROM user"
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user ORDER BY user_id DESC",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc']
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user ORDER BY create_at ASC, user_id DESC",
                [],
                "SELECT * FROM user",
                ['create_at' => 'asc', 'user_id' => 'desc']
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user ORDER BY COALESCE(update_at, create_at) ASC, user_id DESC",
                [],
                "SELECT * FROM user",
                ['COALESCE(update_at, create_at)' => 'asc', 'user_id' => 'desc']
            ],

            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user WHERE gender = :gender ORDER BY user_id DESC",
                [':gender' => new PdoParameter(1, PDO::PARAM_INT)],
                "SELECT * FROM user WHERE gender = :gender",
                ['user_id' => 'desc'],
                ['gender'  => 1]
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user WHERE gender = :gender ORDER BY user_id DESC",
                [':gender' => new PdoParameter(1, PDO::PARAM_INT)],
                "SELECT * FROM user WHERE gender = :gender",
                ['user_id' => 'desc'],
                ['gender'  => Gender::MALE()]
            ],
            [
                ['sqlite', 'mysql'],
                "SELECT * FROM user WHERE gender = :gender AND create_at > :create_at",
                [':gender' => new PdoParameter(1, PDO::PARAM_INT), ':create_at' => new PdoParameter('2001-02-03 04:05:06', PDO::PARAM_STR)],
                "SELECT * FROM user WHERE gender = :gender AND create_at > :create_at",
                null,
                ['gender'  => Gender::MALE(), 'create_at' => DateTime::now()]
            ],
            [
                ['pgsql'],
                "SELECT * FROM user WHERE gender = :gender AND create_at > :create_at",
                [':gender' => new PdoParameter(1, PDO::PARAM_INT), ':create_at' => new PdoParameter('2001-02-03 04:05:06+0000', PDO::PARAM_STR)],
                "SELECT * FROM user WHERE gender = :gender AND create_at > :create_at",
                null,
                ['gender'  => Gender::MALE(), 'create_at' => DateTime::now()]
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user WHERE gender IN (:gender__0, :gender__1)",
                [':gender__0' => new PdoParameter(1, PDO::PARAM_INT), ':gender__1' => new PdoParameter(2, PDO::PARAM_INT)],
                "SELECT * FROM user WHERE gender IN (:gender)",
                null,
                ['gender' => [1, 2]]
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user WHERE gender IN (:gender__0)",
                [':gender__0' => new PdoParameter(1, PDO::PARAM_INT)],
                "SELECT * FROM user WHERE gender IN (:gender)",
                null,
                ['gender' => [1]]
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user WHERE gender IN (:gender__0, :gender__1)",
                [':gender__0' => new PdoParameter(1, PDO::PARAM_INT), ':gender__1' => new PdoParameter(2, PDO::PARAM_INT)],
                "SELECT * FROM user WHERE gender IN (:gender)",
                null,
                ['gender' => [Gender::MALE(), Gender::FEMALE()]]
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "INSERT INTO foo VALUES (:values__0, :values__1, :values__2)",
                [':values__0' => new PdoParameter(1, PDO::PARAM_INT), ':values__1' => new PdoParameter(null, PDO::PARAM_NULL), ':values__2' => new PdoParameter('a', PDO::PARAM_STR)],
                "INSERT INTO foo VALUES (:values)",
                null,
                ['values' => [1, null, 'a']]
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "INSERT INTO foo VALUES (:values__0, :values__1, :values__2)",
                [':values__0' => new PdoParameter(1, PDO::PARAM_INT), ':values__1' => new PdoParameter(null, PDO::PARAM_NULL), ':values__2' => new PdoParameter('a', PDO::PARAM_STR)],
                "INSERT INTO foo VALUES (:values)",
                null,
                ['values' => ['foo_id' => 1, 'bar' => null, 'baz' => 'a']]
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user ORDER BY user_id DESC LIMIT 11 OFFSET 0",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc'],
                null,
                Pager::resolve()   // [1] (2)
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user ORDER BY user_id DESC LIMIT 11 OFFSET 20",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc'],
                null,
                Pager::resolve()->page(3)   // [3] (4)
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user ORDER BY user_id DESC LIMIT 16 OFFSET 30",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc'],
                null,
                Pager::resolve()->page(3)->size(15)   // [3] (4)
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user ORDER BY user_id DESC LIMIT 21 OFFSET 0",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc'],
                null,
                Pager::resolve()->eachSide(1) // [1] 2 (3)
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user ORDER BY user_id DESC LIMIT 41 OFFSET 0",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc'],
                null,
                Pager::resolve()->eachSide(2) // [1] 2 3 4 (5)
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user ORDER BY user_id DESC LIMIT 61 OFFSET 0",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc'],
                null,
                Pager::resolve()->eachSide(3) // [1] 2 3 4 5 6 (7)
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user ORDER BY user_id DESC LIMIT 51 OFFSET 10",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc'],
                null,
                Pager::resolve()->eachSide(3)->page(2) // 1 [2] 3 4 5 6 (7)
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user ORDER BY user_id DESC LIMIT 41 OFFSET 20",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc'],
                null,
                Pager::resolve()->eachSide(3)->page(3) // 1 2 [3] 4 5 6 (7)
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user ORDER BY user_id DESC LIMIT 31 OFFSET 30",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc'],
                null,
                Pager::resolve()->eachSide(3)->page(4) // 1 2 3 [4] 5 6 (7)
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user ORDER BY user_id DESC LIMIT 31 OFFSET 40",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc'],
                null,
                Pager::resolve()->eachSide(3)->page(5) // 2 3 4 [5] 6 7 (8)
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user ORDER BY user_id DESC LIMIT 31 OFFSET 50",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc'],
                null,
                Pager::resolve()->eachSide(3)->page(6) // 3 4 5 [6] 7 8 (9)
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user ORDER BY user_id DESC LIMIT 11 OFFSET 0",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc'],
                null,
                Pager::resolve()->eachSide(3)->needTotal(true) // [1] (<2>) <3> <4> <5> <6> <7>
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user ORDER BY user_id DESC LIMIT 11 OFFSET 50",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc'],
                null,
                Pager::resolve()->eachSide(3)->needTotal(true)->page(6) // 3 4 5 [6] (<7>) <8> <9>
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user WHERE (user_id >= :cursor__0) ORDER BY user_id ASC LIMIT 11 OFFSET 0",
                [':cursor__0' => new PdoParameter(21, PDO::PARAM_INT)],
                "SELECT * FROM user",
                $order_by = ['user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(3),   // {[3]} (4)
                Cursor::create($order_by, $pager, ['user_id' => 21], null)
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user WHERE birthday < CURRENT_TIMESTAMP - INTERVAL 20 YEAR AND ((gender = :cursor__0 AND user_id >= :cursor__1) OR (gender < :cursor__0)) ORDER BY gender DESC, user_id ASC LIMIT 11 OFFSET 0",
                [':cursor__0' => new PdoParameter(2, PDO::PARAM_INT), ':cursor__1' => new PdoParameter(21, PDO::PARAM_INT)],
                "SELECT * FROM user WHERE birthday < CURRENT_TIMESTAMP - INTERVAL 20 YEAR",
                $order_by = ['gender' => 'desc', 'user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(3),   // {[3]} (4)
                Cursor::create($order_by, $pager, ['gender' => Gender::FEMALE(), 'user_id' => 21], null)
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user HAVING foo = 1 AND ((user_id >= :cursor__0)) ORDER BY user_id ASC LIMIT 11 OFFSET 0",
                [':cursor__0' => new PdoParameter(21, PDO::PARAM_INT)],
                "SELECT * FROM user HAVING foo = 1",
                $order_by = ['user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(3),   // {[3]} (4)
                Cursor::create($order_by, $pager, ['user_id' => 21], null)
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user WHERE foo = 1 HAVING bar = 1 AND ((user_id >= :cursor__0)) ORDER BY user_id ASC LIMIT 11 OFFSET 0",
                [':cursor__0' => new PdoParameter(21, PDO::PARAM_INT)],
                "SELECT * FROM user WHERE foo = 1 HAVING bar = 1",
                $order_by = ['user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(3),   // {[3]} (4)
                Cursor::create($order_by, $pager, ['user_id' => 21], null)
            ],
            [
                ['sqlite', 'mysql'],
                "SELECT * FROM user WHERE (gender = :cursor__0 AND update_at = :cursor__1 AND user_id >= :cursor__2) OR (gender = :cursor__0 AND update_at < :cursor__1) OR (gender < :cursor__0) ORDER BY gender DESC, update_at DESC, user_id ASC LIMIT 11 OFFSET 0",
                [':cursor__0' => new PdoParameter(2, PDO::PARAM_INT), ':cursor__1' => new PdoParameter('2000-12-18 12:34:56', PDO::PARAM_STR), ':cursor__2' => new PdoParameter(21, PDO::PARAM_INT)],
                "SELECT * FROM user",
                $order_by = ['gender' => 'desc', 'update_at' => 'desc', 'user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(3),   // {[3]} (4)
                Cursor::create($order_by, $pager, ['gender' => Gender::FEMALE(), 'update_at' => DateTime::createDateTime('2000-12-18 12:34:56'), 'user_id' => 21], null)
            ],
            [
                ['pgsql'],
                "SELECT * FROM user WHERE (gender = :cursor__0 AND update_at = :cursor__1 AND user_id >= :cursor__2) OR (gender = :cursor__0 AND update_at < :cursor__1) OR (gender < :cursor__0) ORDER BY gender DESC, update_at DESC, user_id ASC LIMIT 11 OFFSET 0",
                [':cursor__0' => new PdoParameter(2, PDO::PARAM_INT), ':cursor__1' => new PdoParameter('2000-12-18 12:34:56+0000', PDO::PARAM_STR), ':cursor__2' => new PdoParameter(21, PDO::PARAM_INT)],
                "SELECT * FROM user",
                $order_by = ['gender' => 'desc', 'update_at' => 'desc', 'user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(3),   // {[3]} (4)
                Cursor::create($order_by, $pager, ['gender' => Gender::FEMALE(), 'update_at' => DateTime::createDateTime('2000-12-18 12:34:56'), 'user_id' => 21], null)
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user WHERE (gender = :cursor__0 AND user_id >= :cursor__1) OR (gender < :cursor__0) ORDER BY gender DESC, user_id ASC LIMIT 11 OFFSET 20",
                [':cursor__0' => new PdoParameter(2, PDO::PARAM_INT), ':cursor__1' => new PdoParameter(21, PDO::PARAM_INT)],
                "SELECT * FROM user",
                $order_by = ['gender' => 'desc', 'user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(5),   // {3} 4 [5] (6)
                Cursor::create($order_by, $pager->prev(2), ['gender' => Gender::FEMALE(), 'user_id' => 21], null)
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user WHERE (gender = :cursor__0 AND user_id >= :cursor__1) OR (gender < :cursor__0) ORDER BY gender ASC, user_id DESC LIMIT 11 OFFSET 10",
                [':cursor__0' => new PdoParameter(2, PDO::PARAM_INT), ':cursor__1' => new PdoParameter(21, PDO::PARAM_INT)],
                "SELECT * FROM user",
                $order_by = ['gender' => 'desc', 'user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(5),   // [5] (6) {7} ?
                Cursor::create($order_by, $pager->next(2), ['gender' => Gender::FEMALE(), 'user_id' => 21], null)
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user WHERE (gender = :cursor__0 AND user_id <= :cursor__1) OR (gender > :cursor__0) ORDER BY gender DESC, user_id ASC LIMIT 11 OFFSET 20",
                [':cursor__0' => new PdoParameter(2, PDO::PARAM_INT), ':cursor__1' => new PdoParameter(21, PDO::PARAM_INT)],
                "SELECT * FROM user",
                $order_by = ['gender' => 'desc', 'user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(3),   // [3] (4) 5 6 7 8 9 {10} ? (near by first)
                Cursor::create($order_by, $pager->next(7), ['gender' => Gender::FEMALE(), 'user_id' => 21], null)
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user WHERE (user_id >= :cursor__0) ORDER BY user_id DESC LIMIT 11 OFFSET 10",
                [':cursor__0' => new PdoParameter(21, PDO::PARAM_INT)],
                "SELECT * FROM user",
                $order_by = ['user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(9)->eachSide(5),   // [9] (10) {11} 12 13 14 15
                Cursor::create($order_by, $pager->next(2), ['user_id' => 21], 4)
            ],
        ];
    }

    /**
     * @dataProvider dataCompiles
     */
    public function test_compile(array $target_db_kinds, string $expect_sql, array $expect_params, string $sql, ?array $order_by = null, ?array $params = null, ?Pager $pager = null, ?Cursor $cursor = null)
    {
        foreach (['sqlite', 'mysql', 'pgsql'] as $db_kind) {
            if (!in_array($db_kind, $target_db_kinds)) {
                continue;
            }
            try {
                $db = Dao::db($db_kind);
            } catch (\Exception $e) {
                $this->markTestSkipped("Database '$db_kind' was not ready.");
                continue;
            }
            [$compiled_sql, $compiled_params] = $this->compiler->compile($db, $sql, OrderBy::valueOf($order_by), $params, $pager, $cursor);
            $this->assertEquals($expect_sql, $compiled_sql, "on DB '{$db_kind}'");
            $this->assertEquals($expect_params, $compiled_params, "on DB '{$db_kind}'");
        }
    }

    // public function dataPagings() : array
    // {
    //     return [
    //         [],
    //     ];
    // }

    // /**
    //  * @dataProvider dataPagings
    //  */
    // public function test_paging(array $target_db_kinds, string $expect_data, array $expect_cursor, string $sql, ?array $order_by = null, ?array $params = null, ?Pager $pager = null, ?Cursor $cursor = null)
    // {
    //     foreach (['sqlite', 'mysql', 'pgsql'] as $db_kind) {
    //         if (!in_array($db_kind, $target_db_kinds)) {
    //             continue;
    //         }
    //         try {
    //             $db = Dao::db($db_kind);
    //         } catch (\Exception $e) {
    //             $this->markTestSkipped("Database '$db_kind' was not ready.");
    //             continue;
    //         }
    //         // [$compiled_sql, $compiled_params] = $this->compiler->compile($db, $sql, OrderBy::valueOf($order_by), $params, $pager, $cursor);
    //         // $this->assertEquals($expect_sql, $compiled_sql, "on DB '{$db_kind}'");
    //         // $this->assertEquals($expect_params, $compiled_params, "on DB '{$db_kind}'");
    //     }
    // }
}
