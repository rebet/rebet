<?php
namespace Rebet\Tests\Database\Compiler;

use App\Enum\Gender;
use Rebet\Database\Database;
use Rebet\Database\Expression;
use Rebet\Database\OrderBy;
use Rebet\Database\Pagination\Cursor;
use Rebet\Database\Pagination\Pager;
use Rebet\Database\PdoParameter;
use Rebet\Tests\RebetDatabaseTestCase;
use Rebet\Tools\DateTime\DateTime;

class BuiltinCompilerTest extends RebetDatabaseTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        DateTime::setTestNow('2001-02-03 04:05:06');
    }

    public function dataCompiles() : array
    {
        $this->setUp();
        return [
            [
                ['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv'],
                "SELECT \* FROM user",
                [],
                "SELECT * FROM user"
            ],
            [
                ['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv'],
                "SELECT \* FROM user ORDER BY ?user_id? DESC",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc']
            ],
            [
                ['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv'],
                "SELECT \* FROM user ORDER BY ?created_at? ASC, ?user_id? DESC",
                [],
                "SELECT * FROM user",
                ['created_at' => 'asc', 'user_id' => 'desc']
            ],
            [
                ['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv'],
                "SELECT \* FROM user ORDER BY COALESCE(update_at, created_at) ASC, ?user_id? DESC",
                [],
                "SELECT * FROM user",
                ['COALESCE(update_at, created_at)' => 'asc', 'user_id' => 'desc']
            ],
            [
                ['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv'],
                "SELECT \* FROM user WHERE gender = :gender ORDER BY ?user_id? DESC",
                [':gender' => PdoParameter::int(1)],
                "SELECT * FROM user WHERE gender = :gender",
                ['user_id' => 'desc'],
                ['gender'  => 1]
            ],
            [
                ['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv'],
                "SELECT \* FROM user WHERE gender = :gender ORDER BY ?user_id? DESC",
                [':gender' => PdoParameter::int(1)],
                "SELECT * FROM user WHERE gender = :gender",
                ['user_id' => 'desc'],
                ['gender'  => Gender::MALE()]
            ],
            [
                ['sqlite', 'mysql', 'mariadb', 'sqlsrv'],
                "SELECT \* FROM user WHERE gender = :gender AND created_at > :created_at",
                [':gender' => PdoParameter::int(1), ':created_at' => PdoParameter::str('2001-02-03 04:05:06')],
                "SELECT * FROM user WHERE gender = :gender AND created_at > :created_at",
                null,
                ['gender'  => Gender::MALE(), 'created_at' => DateTime::now()]
            ],
            [
                ['pgsql'],
                "SELECT \* FROM user WHERE gender = :gender AND created_at > :created_at",
                [':gender' => PdoParameter::int(1), ':created_at' => PdoParameter::str('2001-02-03 04:05:06+0000')],
                "SELECT * FROM user WHERE gender = :gender AND created_at > :created_at",
                null,
                ['gender'  => Gender::MALE(), 'created_at' => DateTime::now()]
            ],
            [
                ['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv'],
                "SELECT \* FROM user WHERE gender IN (:gender__0, :gender__1)",
                [':gender__0' => PdoParameter::int(1), ':gender__1' => PdoParameter::int(2)],
                "SELECT * FROM user WHERE gender IN (:gender)",
                null,
                ['gender' => [1, 2]]
            ],
            [
                ['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv'],
                "SELECT \* FROM user WHERE gender IN (:gender__0)",
                [':gender__0' => PdoParameter::int(1)],
                "SELECT * FROM user WHERE gender IN (:gender)",
                null,
                ['gender' => [1]]
            ],
            [
                ['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv'],
                "SELECT \* FROM user WHERE gender = :gender__0 AND (gender = :gender__1) AND gender = :gender__2",
                [':gender__0' => PdoParameter::int(1), ':gender__1' => PdoParameter::int(1), ':gender__2' => PdoParameter::int(1)],
                "SELECT * FROM user WHERE gender = :gender AND (gender = :gender) AND gender = :gender",
                [],
                ['gender'  => 1]
            ],
            [
                ['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv'],
                "SELECT \* FROM user WHERE gender IN (:gender__0__0, :gender__0__1) AND gender IN (:gender__1__0, :gender__1__1)",
                [':gender__0__0' => PdoParameter::int(1), ':gender__0__1' => PdoParameter::int(2), ':gender__1__0' => PdoParameter::int(1), ':gender__1__1' => PdoParameter::int(2)],
                "SELECT * FROM user WHERE gender IN (:gender) AND gender IN (:gender)",
                null,
                ['gender' => [1, 2]]
            ],
            [
                ['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv'],
                "SELECT \* FROM user WHERE gender IN (:gender__0, :gender__1)",
                [':gender__0' => PdoParameter::int(1), ':gender__1' => PdoParameter::int(2)],
                "SELECT * FROM user WHERE gender IN (:gender)",
                null,
                ['gender' => [Gender::MALE(), Gender::FEMALE()]]
            ],
            [
                ['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv'],
                "INSERT INTO foo VALUES (:values__0, :values__1, :values__2)",
                [':values__0' => PdoParameter::int(1), ':values__1' => PdoParameter::null(), ':values__2' => PdoParameter::str('a')],
                "INSERT INTO foo VALUES (:values)",
                null,
                ['values' => [1, null, 'a']]
            ],
            [
                ['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv'],
                "INSERT INTO foo VALUES (:values__0, :values__1, :values__2)",
                [':values__0' => PdoParameter::int(1), ':values__1' => PdoParameter::null(), ':values__2' => PdoParameter::str('a')],
                "INSERT INTO foo VALUES (:values)",
                null,
                ['values' => ['foo_id' => 1, 'bar' => null, 'baz' => 'a']]
            ],
            [
                ['sqlite', 'mysql', 'mariadb', 'pgsql'],
                "SELECT \* FROM user ORDER BY ?user_id? DESC LIMIT 11",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc'],
                null,
                Pager::resolve()   // [1] (2)
            ],
            [
                ['sqlsrv'],
                "SELECT TOP 11 \* FROM user ORDER BY ?user_id? DESC",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc'],
                null,
                Pager::resolve()   // [1] (2)
            ],
            [
                ['sqlite', 'mysql', 'mariadb', 'pgsql'],
                "SELECT \* FROM user ORDER BY ?user_id? DESC LIMIT 11 OFFSET 20",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc'],
                null,
                Pager::resolve()->page(3)   // [3] (4)
            ],
            [
                ['sqlsrv'],
                "SELECT \* FROM user ORDER BY ?user_id? DESC OFFSET 20 ROWS FETCH NEXT 11 ROWS ONLY",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc'],
                null,
                Pager::resolve()->page(3)   // [3] (4)
            ],
            [
                ['sqlite', 'mysql', 'mariadb', 'pgsql'],
                "SELECT \* FROM user ORDER BY ?user_id? DESC LIMIT 16 OFFSET 30",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc'],
                null,
                Pager::resolve()->page(3)->size(15)   // [3] (4)
            ],
            [
                ['sqlsrv'],
                "SELECT \* FROM user ORDER BY ?user_id? DESC OFFSET 30 ROWS FETCH NEXT 16 ROWS ONLY",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc'],
                null,
                Pager::resolve()->page(3)->size(15)   // [3] (4)
            ],
            [
                ['sqlite', 'mysql', 'mariadb', 'pgsql'],
                "SELECT \* FROM user ORDER BY ?user_id? DESC LIMIT 21",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc'],
                null,
                Pager::resolve()->eachSide(1) // [1] 2 (3)
            ],
            [
                ['sqlsrv'],
                "SELECT TOP 21 \* FROM user ORDER BY ?user_id? DESC",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc'],
                null,
                Pager::resolve()->eachSide(1) // [1] 2 (3)
            ],
            [
                ['sqlite', 'mysql', 'mariadb', 'pgsql'],
                "SELECT \* FROM user ORDER BY ?user_id? DESC LIMIT 41",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc'],
                null,
                Pager::resolve()->eachSide(2) // [1] 2 3 4 (5)
            ],
            [
                ['sqlsrv'],
                "SELECT TOP 41 \* FROM user ORDER BY ?user_id? DESC",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc'],
                null,
                Pager::resolve()->eachSide(2) // [1] 2 3 4 (5)
            ],
            [
                ['sqlite', 'mysql', 'mariadb', 'pgsql'],
                "SELECT \* FROM user ORDER BY ?user_id? DESC LIMIT 61",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc'],
                null,
                Pager::resolve()->eachSide(3) // [1] 2 3 4 5 6 (7)
            ],
            [
                ['sqlsrv'],
                "SELECT TOP 61 \* FROM user ORDER BY ?user_id? DESC",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc'],
                null,
                Pager::resolve()->eachSide(3) // [1] 2 3 4 5 6 (7)
            ],
            [
                ['sqlite', 'mysql', 'mariadb', 'pgsql'],
                "SELECT \* FROM user ORDER BY ?user_id? DESC LIMIT 51 OFFSET 10",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc'],
                null,
                Pager::resolve()->eachSide(3)->page(2) // 1 [2] 3 4 5 6 (7)
            ],
            [
                ['sqlsrv'],
                "SELECT \* FROM user ORDER BY ?user_id? DESC OFFSET 10 ROWS FETCH NEXT 51 ROWS ONLY",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc'],
                null,
                Pager::resolve()->eachSide(3)->page(2) // 1 [2] 3 4 5 6 (7)
            ],
            [
                ['sqlite', 'mysql', 'mariadb', 'pgsql'],
                "SELECT \* FROM user ORDER BY ?user_id? DESC LIMIT 41 OFFSET 20",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc'],
                null,
                Pager::resolve()->eachSide(3)->page(3) // 1 2 [3] 4 5 6 (7)
            ],
            [
                ['sqlsrv'],
                "SELECT \* FROM user ORDER BY ?user_id? DESC OFFSET 20 ROWS FETCH NEXT 41 ROWS ONLY",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc'],
                null,
                Pager::resolve()->eachSide(3)->page(3) // 1 2 [3] 4 5 6 (7)
            ],
            [
                ['sqlite', 'mysql', 'mariadb', 'pgsql'],
                "SELECT \* FROM user ORDER BY ?user_id? DESC LIMIT 31 OFFSET 30",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc'],
                null,
                Pager::resolve()->eachSide(3)->page(4) // 1 2 3 [4] 5 6 (7)
            ],
            [
                ['sqlsrv'],
                "SELECT \* FROM user ORDER BY ?user_id? DESC OFFSET 30 ROWS FETCH NEXT 31 ROWS ONLY",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc'],
                null,
                Pager::resolve()->eachSide(3)->page(4) // 1 2 3 [4] 5 6 (7)
            ],
            [
                ['sqlite', 'mysql', 'mariadb', 'pgsql'],
                "SELECT \* FROM user ORDER BY ?user_id? DESC LIMIT 31 OFFSET 40",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc'],
                null,
                Pager::resolve()->eachSide(3)->page(5) // 2 3 4 [5] 6 7 (8)
            ],
            [
                ['sqlsrv'],
                "SELECT \* FROM user ORDER BY ?user_id? DESC OFFSET 40 ROWS FETCH NEXT 31 ROWS ONLY",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc'],
                null,
                Pager::resolve()->eachSide(3)->page(5) // 2 3 4 [5] 6 7 (8)
            ],
            [
                ['sqlite', 'mysql', 'mariadb', 'pgsql'],
                "SELECT \* FROM user ORDER BY ?user_id? DESC LIMIT 31 OFFSET 50",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc'],
                null,
                Pager::resolve()->eachSide(3)->page(6) // 3 4 5 [6] 7 8 (9)
            ],
            [
                ['sqlsrv'],
                "SELECT \* FROM user ORDER BY ?user_id? DESC OFFSET 50 ROWS FETCH NEXT 31 ROWS ONLY",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc'],
                null,
                Pager::resolve()->eachSide(3)->page(6) // 3 4 5 [6] 7 8 (9)
            ],
            [
                ['sqlite', 'mysql', 'mariadb', 'pgsql'],
                "SELECT \* FROM user ORDER BY ?user_id? DESC LIMIT 11",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc'],
                null,
                Pager::resolve()->eachSide(3)->needTotal(true) // [1] (<2>) <3> <4> <5> <6> <7>
            ],
            [
                ['sqlsrv'],
                "SELECT TOP 11 \* FROM user ORDER BY ?user_id? DESC",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc'],
                null,
                Pager::resolve()->eachSide(3)->needTotal(true) // [1] (<2>) <3> <4> <5> <6> <7>
            ],
            [
                ['sqlite', 'mysql', 'mariadb', 'pgsql'],
                "SELECT \* FROM user ORDER BY ?user_id? DESC LIMIT 11 OFFSET 50",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc'],
                null,
                Pager::resolve()->eachSide(3)->needTotal(true)->page(6) // 3 4 5 [6] (<7>) <8> <9>
            ],
            [
                ['sqlsrv'],
                "SELECT \* FROM user ORDER BY ?user_id? DESC OFFSET 50 ROWS FETCH NEXT 11 ROWS ONLY",
                [],
                "SELECT * FROM user",
                ['user_id' => 'desc'],
                null,
                Pager::resolve()->eachSide(3)->needTotal(true)->page(6) // 3 4 5 [6] (<7>) <8> <9>
            ],
            [
                ['sqlite', 'mysql', 'mariadb', 'pgsql'],
                "SELECT \* FROM user WHERE (?user_id? >= :cursor__0__0) ORDER BY ?user_id? ASC LIMIT 11",
                [':cursor__0__0' => PdoParameter::int(21)],
                "SELECT * FROM user",
                $order_by = ['user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(3),   // {[3]} (4)
                Cursor::create($order_by, $pager, ['user_id' => 21], null)
            ],
            [
                ['sqlsrv'],
                "SELECT TOP 11 \* FROM user WHERE (?user_id? >= :cursor__0__0) ORDER BY ?user_id? ASC",
                [':cursor__0__0' => PdoParameter::int(21)],
                "SELECT * FROM user",
                $order_by = ['user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(3),   // {[3]} (4)
                Cursor::create($order_by, $pager, ['user_id' => 21], null)
            ],
            [
                ['sqlite', 'mysql', 'mariadb', 'pgsql'],
                "SELECT \* FROM user WHERE birthday < CURRENT_TIMESTAMP - INTERVAL 20 YEAR AND ((?gender? = :cursor__0__0 AND ?user_id? >= :cursor__0__1) OR (?gender? < :cursor__1__0)) ORDER BY ?gender? DESC, ?user_id? ASC LIMIT 11",
                [':cursor__0__0' => PdoParameter::int(2), ':cursor__0__1' => PdoParameter::int(21), ':cursor__1__0' => PdoParameter::int(2)],
                "SELECT * FROM user WHERE birthday < CURRENT_TIMESTAMP - INTERVAL 20 YEAR",
                $order_by = ['gender' => 'desc', 'user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(3),   // {[3]} (4)
                Cursor::create($order_by, $pager, ['gender' => Gender::FEMALE(), 'user_id' => 21], null)
            ],
            [
                ['sqlsrv'],
                "SELECT TOP 11 \* FROM user WHERE birthday < CURRENT_TIMESTAMP - INTERVAL 20 YEAR AND ((?gender? = :cursor__0__0 AND ?user_id? >= :cursor__0__1) OR (?gender? < :cursor__1__0)) ORDER BY ?gender? DESC, ?user_id? ASC",
                [':cursor__0__0' => PdoParameter::int(2), ':cursor__0__1' => PdoParameter::int(21), ':cursor__1__0' => PdoParameter::int(2)],
                "SELECT * FROM user WHERE birthday < CURRENT_TIMESTAMP - INTERVAL 20 YEAR",
                $order_by = ['gender' => 'desc', 'user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(3),   // {[3]} (4)
                Cursor::create($order_by, $pager, ['gender' => Gender::FEMALE(), 'user_id' => 21], null)
            ],
            [
                ['sqlite', 'mysql', 'mariadb', 'pgsql'],
                "SELECT \* FROM user HAVING foo = 1 AND ((?user_id? >= :cursor__0__0)) ORDER BY ?user_id? ASC LIMIT 11",
                [':cursor__0__0' => PdoParameter::int(21)],
                "SELECT * FROM user HAVING foo = 1",
                $order_by = ['user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(3),   // {[3]} (4)
                Cursor::create($order_by, $pager, ['user_id' => 21], null)
            ],
            [
                ['sqlsrv'],
                "SELECT TOP 11 \* FROM user HAVING foo = 1 AND ((?user_id? >= :cursor__0__0)) ORDER BY ?user_id? ASC",
                [':cursor__0__0' => PdoParameter::int(21)],
                "SELECT * FROM user HAVING foo = 1",
                $order_by = ['user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(3),   // {[3]} (4)
                Cursor::create($order_by, $pager, ['user_id' => 21], null)
            ],
            [
                ['sqlite', 'mysql', 'mariadb', 'pgsql'],
                "SELECT \* FROM user WHERE foo = 1 HAVING bar = 1 AND ((?user_id? >= :cursor__0__0)) ORDER BY ?user_id? ASC LIMIT 11",
                [':cursor__0__0' => PdoParameter::int(21)],
                "SELECT * FROM user WHERE foo = 1 HAVING bar = 1",
                $order_by = ['user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(3),   // {[3]} (4)
                Cursor::create($order_by, $pager, ['user_id' => 21], null)
            ],
            [
                ['sqlsrv'],
                "SELECT TOP 11 \* FROM user WHERE foo = 1 HAVING bar = 1 AND ((?user_id? >= :cursor__0__0)) ORDER BY ?user_id? ASC",
                [':cursor__0__0' => PdoParameter::int(21)],
                "SELECT * FROM user WHERE foo = 1 HAVING bar = 1",
                $order_by = ['user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(3),   // {[3]} (4)
                Cursor::create($order_by, $pager, ['user_id' => 21], null)
            ],
            [
                ['sqlite', 'mysql', 'mariadb'],
                "SELECT \* FROM user WHERE (?gender? = :cursor__0__0 AND ?update_at? = :cursor__0__1 AND ?user_id? >= :cursor__0__2) OR (?gender? = :cursor__1__0 AND ?update_at? < :cursor__1__1) OR (?gender? < :cursor__2__0) ORDER BY ?gender? DESC, ?update_at? DESC, ?user_id? ASC LIMIT 11",
                [':cursor__0__0' => PdoParameter::int(2), ':cursor__0__1' => PdoParameter::str('2000-12-18 12:34:56'), ':cursor__0__2' => PdoParameter::int(21), ':cursor__1__0' => PdoParameter::int(2), ':cursor__1__1' => PdoParameter::str('2000-12-18 12:34:56'), ':cursor__2__0' => PdoParameter::int(2)],
                "SELECT * FROM user",
                $order_by = ['gender' => 'desc', 'update_at' => 'desc', 'user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(3),   // {[3]} (4)
                Cursor::create($order_by, $pager, ['gender' => Gender::FEMALE(), 'update_at' => DateTime::createDateTime('2000-12-18 12:34:56'), 'user_id' => 21], null)
            ],
            [
                ['pgsql'],
                "SELECT \* FROM user WHERE (?gender? = :cursor__0__0 AND ?update_at? = :cursor__0__1 AND ?user_id? >= :cursor__0__2) OR (?gender? = :cursor__1__0 AND ?update_at? < :cursor__1__1) OR (?gender? < :cursor__2__0) ORDER BY ?gender? DESC, ?update_at? DESC, ?user_id? ASC LIMIT 11",
                [':cursor__0__0' => PdoParameter::int(2), ':cursor__0__1' => PdoParameter::str('2000-12-18 12:34:56+0000'), ':cursor__0__2' => PdoParameter::int(21), ':cursor__1__0' => PdoParameter::int(2), ':cursor__1__1' => PdoParameter::str('2000-12-18 12:34:56+0000'), ':cursor__2__0' => PdoParameter::int(2)],
                "SELECT * FROM user",
                $order_by = ['gender' => 'desc', 'update_at' => 'desc', 'user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(3),   // {[3]} (4)
                Cursor::create($order_by, $pager, ['gender' => Gender::FEMALE(), 'update_at' => DateTime::createDateTime('2000-12-18 12:34:56'), 'user_id' => 21], null)
            ],
            [
                ['sqlsrv'],
                "SELECT TOP 11 \* FROM user WHERE (?gender? = :cursor__0__0 AND ?update_at? = :cursor__0__1 AND ?user_id? >= :cursor__0__2) OR (?gender? = :cursor__1__0 AND ?update_at? < :cursor__1__1) OR (?gender? < :cursor__2__0) ORDER BY ?gender? DESC, ?update_at? DESC, ?user_id? ASC",
                [':cursor__0__0' => PdoParameter::int(2), ':cursor__0__1' => PdoParameter::str('2000-12-18 12:34:56'), ':cursor__0__2' => PdoParameter::int(21), ':cursor__1__0' => PdoParameter::int(2), ':cursor__1__1' => PdoParameter::str('2000-12-18 12:34:56'), ':cursor__2__0' => PdoParameter::int(2)],
                "SELECT * FROM user",
                $order_by = ['gender' => 'desc', 'update_at' => 'desc', 'user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(3),   // {[3]} (4)
                Cursor::create($order_by, $pager, ['gender' => Gender::FEMALE(), 'update_at' => DateTime::createDateTime('2000-12-18 12:34:56'), 'user_id' => 21], null)
            ],
            [
                ['sqlite', 'mysql', 'mariadb', 'pgsql'],
                "SELECT \* FROM user WHERE (?gender? = :cursor__0__0 AND ?user_id? >= :cursor__0__1) OR (?gender? < :cursor__1__0) ORDER BY ?gender? DESC, ?user_id? ASC LIMIT 11 OFFSET 20",
                [':cursor__0__0' => PdoParameter::int(2), ':cursor__0__1' => PdoParameter::int(21), ':cursor__1__0' => PdoParameter::int(2)],
                "SELECT * FROM user",
                $order_by = ['gender' => 'desc', 'user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(5),   // {3} 4 [5] (6)
                Cursor::create($order_by, $pager->prev(2), ['gender' => Gender::FEMALE(), 'user_id' => 21], null)
            ],
            [
                ['sqlsrv'],
                "SELECT \* FROM user WHERE (?gender? = :cursor__0__0 AND ?user_id? >= :cursor__0__1) OR (?gender? < :cursor__1__0) ORDER BY ?gender? DESC, ?user_id? ASC OFFSET 20 ROWS FETCH NEXT 11 ROWS ONLY",
                [':cursor__0__0' => PdoParameter::int(2), ':cursor__0__1' => PdoParameter::int(21), ':cursor__1__0' => PdoParameter::int(2)],
                "SELECT * FROM user",
                $order_by = ['gender' => 'desc', 'user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(5),   // {3} 4 [5] (6)
                Cursor::create($order_by, $pager->prev(2), ['gender' => Gender::FEMALE(), 'user_id' => 21], null)
            ],
            [
                ['sqlite', 'mysql', 'mariadb', 'pgsql'],
                "SELECT \* FROM user WHERE (?gender? = :cursor__0__0 AND ?user_id? <= :cursor__0__1) OR (?gender? > :cursor__1__0) ORDER BY ?gender? ASC, ?user_id? DESC LIMIT 11 OFFSET 10",
                [':cursor__0__0' => PdoParameter::int(2), ':cursor__0__1' => PdoParameter::int(21), ':cursor__1__0' => PdoParameter::int(2)],
                "SELECT * FROM user",
                $order_by = ['gender' => 'desc', 'user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(5),   // [5] (6) {7} ?
                Cursor::create($order_by, $pager->next(2), ['gender' => Gender::FEMALE(), 'user_id' => 21], null)
            ],
            [
                ['sqlsrv'],
                "SELECT \* FROM user WHERE (?gender? = :cursor__0__0 AND ?user_id? <= :cursor__0__1) OR (?gender? > :cursor__1__0) ORDER BY ?gender? ASC, ?user_id? DESC OFFSET 10 ROWS FETCH NEXT 11 ROWS ONLY",
                [':cursor__0__0' => PdoParameter::int(2), ':cursor__0__1' => PdoParameter::int(21), ':cursor__1__0' => PdoParameter::int(2)],
                "SELECT * FROM user",
                $order_by = ['gender' => 'desc', 'user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(5),   // [5] (6) {7} ?
                Cursor::create($order_by, $pager->next(2), ['gender' => Gender::FEMALE(), 'user_id' => 21], null)
            ],
            [
                ['sqlite', 'mysql', 'mariadb', 'pgsql'],
                "SELECT \* FROM user WHERE (?gender? = :cursor__0__0 AND ?user_id? <= :cursor__0__1) OR (?gender? > :cursor__1__0) ORDER BY ?gender? DESC, ?user_id? ASC LIMIT 11 OFFSET 20",
                [':cursor__0__0' => PdoParameter::int(2), ':cursor__0__1' => PdoParameter::int(21), ':cursor__1__0' => PdoParameter::int(2)],
                "SELECT * FROM user",
                $order_by = ['gender' => 'desc', 'user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(3),   // [3] (4) 5 6 7 8 9 {10} ? (near by first)
                Cursor::create($order_by, $pager->next(7), ['gender' => Gender::FEMALE(), 'user_id' => 21], null)
            ],
            [
                ['sqlsrv'],
                "SELECT \* FROM user WHERE (?gender? = :cursor__0__0 AND ?user_id? <= :cursor__0__1) OR (?gender? > :cursor__1__0) ORDER BY ?gender? DESC, ?user_id? ASC OFFSET 20 ROWS FETCH NEXT 11 ROWS ONLY",
                [':cursor__0__0' => PdoParameter::int(2), ':cursor__0__1' => PdoParameter::int(21), ':cursor__1__0' => PdoParameter::int(2)],
                "SELECT * FROM user",
                $order_by = ['gender' => 'desc', 'user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(3),   // [3] (4) 5 6 7 8 9 {10} ? (near by first)
                Cursor::create($order_by, $pager->next(7), ['gender' => Gender::FEMALE(), 'user_id' => 21], null)
            ],
            [
                ['sqlite', 'mysql', 'mariadb', 'pgsql'],
                "SELECT \* FROM user WHERE (?user_id? <= :cursor__0__0) ORDER BY ?user_id? DESC LIMIT 11 OFFSET 10",
                [':cursor__0__0' => PdoParameter::int(21)],
                "SELECT * FROM user",
                $order_by = ['user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(9)->eachSide(5),   // [9] (10) {11} 12 13 14 15
                Cursor::create($order_by, $pager->next(2), ['user_id' => 21], 4)
            ],
            [
                ['sqlsrv'],
                "SELECT \* FROM user WHERE (?user_id? <= :cursor__0__0) ORDER BY ?user_id? DESC OFFSET 10 ROWS FETCH NEXT 11 ROWS ONLY",
                [':cursor__0__0' => PdoParameter::int(21)],
                "SELECT * FROM user",
                $order_by = ['user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(9)->eachSide(5),   // [9] (10) {11} 12 13 14 15
                Cursor::create($order_by, $pager->next(2), ['user_id' => 21], 4)
            ],
            [
                ['sqlite', 'mysql', 'mariadb'],
                "SELECT U.\*, A.article_id, A.created_at AS article_created_at FROM user AS U INNER JOIN article AS A USING(user_id) WHERE U.user_id = 1 AND ((A.created_at = :cursor__0__0 AND ?article_id? >= :cursor__0__1) OR (A.created_at < :cursor__1__0)) ORDER BY ?article_created_at? DESC, ?article_id? ASC LIMIT 11",
                [':cursor__0__0' => PdoParameter::str('2001-02-03 04:05:06'), ':cursor__0__1' => PdoParameter::int(21), ':cursor__1__0' => PdoParameter::str('2001-02-03 04:05:06')],
                "SELECT U.*, A.article_id, A.created_at AS article_created_at FROM user AS U INNER JOIN article AS A USING(user_id) WHERE U.user_id = 1",
                $order_by = ['article_created_at' => 'desc', 'article_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(3),   // {[3]} (4)
                Cursor::create($order_by, $pager, ['article_created_at' => DateTime::now(), 'article_id' => 21], null)
            ],
            [
                ['pgsql'],
                "SELECT U.\*, A.article_id, A.created_at AS article_created_at FROM user AS U INNER JOIN article AS A USING(user_id) WHERE U.user_id = 1 AND ((A.created_at = :cursor__0__0 AND ?article_id? >= :cursor__0__1) OR (A.created_at < :cursor__1__0)) ORDER BY ?article_created_at? DESC, ?article_id? ASC LIMIT 11",
                [':cursor__0__0' => PdoParameter::str('2001-02-03 04:05:06+0000'), ':cursor__0__1' => PdoParameter::int(21), ':cursor__1__0' => PdoParameter::str('2001-02-03 04:05:06+0000')],
                "SELECT U.*, A.article_id, A.created_at AS article_created_at FROM user AS U INNER JOIN article AS A USING(user_id) WHERE U.user_id = 1",
                $order_by = ['article_created_at' => 'desc', 'article_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(3),   // {[3]} (4)
                Cursor::create($order_by, $pager, ['article_created_at' => DateTime::now(), 'article_id' => 21], null)
            ],
            [
                ['sqlsrv'],
                "SELECT TOP 11 U.\*, A.article_id, A.created_at AS article_created_at FROM user AS U INNER JOIN article AS A USING(user_id) WHERE U.user_id = 1 AND ((A.created_at = :cursor__0__0 AND ?article_id? >= :cursor__0__1) OR (A.created_at < :cursor__1__0)) ORDER BY ?article_created_at? DESC, ?article_id? ASC",
                [':cursor__0__0' => PdoParameter::str('2001-02-03 04:05:06'), ':cursor__0__1' => PdoParameter::int(21), ':cursor__1__0' => PdoParameter::str('2001-02-03 04:05:06')],
                "SELECT U.*, A.article_id, A.created_at AS article_created_at FROM user AS U INNER JOIN article AS A USING(user_id) WHERE U.user_id = 1",
                $order_by = ['article_created_at' => 'desc', 'article_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(3),   // {[3]} (4)
                Cursor::create($order_by, $pager, ['article_created_at' => DateTime::now(), 'article_id' => 21], null)
            ],
            [
                ['sqlite', 'mysql', 'mariadb'],
                "SELECT \*, COALESCE(update_at, created_at) as change_at FROM user WHERE (COALESCE(update_at,created_at) = :cursor__0__0 AND ?user_id? >= :cursor__0__1) OR (COALESCE(update_at,created_at) > :cursor__1__0) ORDER BY ?change_at? ASC, ?user_id? ASC LIMIT 11",
                [':cursor__0__0' => PdoParameter::str('2001-02-03 04:05:06'), ':cursor__0__1' => PdoParameter::int(21), ':cursor__1__0' => PdoParameter::str('2001-02-03 04:05:06')],
                "SELECT *, COALESCE(update_at, created_at) as change_at FROM user",
                $order_by = ['change_at' => 'asc', 'user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(3),   // {[3]} (4)
                Cursor::create($order_by, $pager, ['change_at' => DateTime::now(), 'user_id' => 21], null)
            ],
            [
                ['pgsql'],
                "SELECT \*, COALESCE(update_at, created_at) as change_at FROM user WHERE (COALESCE(update_at,created_at) = :cursor__0__0 AND ?user_id? >= :cursor__0__1) OR (COALESCE(update_at,created_at) > :cursor__1__0) ORDER BY ?change_at? ASC, ?user_id? ASC LIMIT 11",
                [':cursor__0__0' => PdoParameter::str('2001-02-03 04:05:06+0000'), ':cursor__0__1' => PdoParameter::int(21), ':cursor__1__0' => PdoParameter::str('2001-02-03 04:05:06+0000')],
                "SELECT *, COALESCE(update_at, created_at) as change_at FROM user",
                $order_by = ['change_at' => 'asc', 'user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(3),   // {[3]} (4)
                Cursor::create($order_by, $pager, ['change_at' => DateTime::now(), 'user_id' => 21], null)
            ],
            [
                ['sqlsrv'],
                "SELECT TOP 11 \*, COALESCE(update_at, created_at) as change_at FROM user WHERE (COALESCE(update_at,created_at) = :cursor__0__0 AND ?user_id? >= :cursor__0__1) OR (COALESCE(update_at,created_at) > :cursor__1__0) ORDER BY ?change_at? ASC, ?user_id? ASC",
                [':cursor__0__0' => PdoParameter::str('2001-02-03 04:05:06'), ':cursor__0__1' => PdoParameter::int(21), ':cursor__1__0' => PdoParameter::str('2001-02-03 04:05:06')],
                "SELECT *, COALESCE(update_at, created_at) as change_at FROM user",
                $order_by = ['change_at' => 'asc', 'user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(3),   // {[3]} (4)
                Cursor::create($order_by, $pager, ['change_at' => DateTime::now(), 'user_id' => 21], null)
            ],
            [
                ['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv'],
                'SELECT \* FROM user WHERE 1=1 AND gender = :gender ORDER BY ?user_id? DESC',
                [':gender' => PdoParameter::int(1)],
                'SELECT * FROM user WHERE 1=1{%if $gender%} AND gender = :gender{%endif%}',
                ['user_id' => 'desc'],
                ['gender'  => 1]
            ],
            [
                ['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv'],
                'SELECT \* FROM user WHERE 1=1 ORDER BY ?user_id? DESC',
                [],
                'SELECT * FROM user WHERE 1=1{%if $gender%} AND gender = :gender{%endif%}',
                ['user_id' => 'desc'],
                ['gender'  => null]
            ],
        ];
    }

    /**
     * @dataProvider dataCompiles
     */
    public function test_compile(array $target_db_kinds, string $expect_sql, array $expect_params, string $sql, ?array $order_by = null, ?array $params = null, ?Pager $pager = null, ?Cursor $cursor = null)
    {
        $this->eachDb(function (Database $db) use ($target_db_kinds, $expect_sql, $expect_params, $sql, $order_by, $params, $pager, $cursor) {
            if (!in_array($db->name(), $target_db_kinds)) {
                return;
            }
            $query = $db->compiler()->compile($sql, OrderBy::valueOf($order_by), $params, $pager, $cursor);
            $this->assertStringWildcardAll($expect_sql, $query->sql(), [], "on DB '{$db->name()}'");
            $this->assertEquals($expect_params, $query->params(), "on DB '{$db->name()}'");
        });
    }

    public function test_paging()
    {
        // This test is not need because of BuiltinCoumpiler::paging() method will be covered by DatabaseTest::test_paginate() test.
        $this->assertTrue(true);
    }

    public function dataConvertParams() : array
    {
        return [
            [':key', [':key' => PdoParameter::int(1)], 'key', 1],
            [':key', [':key' => PdoParameter::int(1)], ':key', 1],
            [':key', [':key' => PdoParameter::str('abc')], 'key', 'abc'],
            [':key__0, :key__1, :key__2', [':key__0' => PdoParameter::int(1), ':key__1' => PdoParameter::int(2), ':key__2' => PdoParameter::int(3)], 'key', [1, 2, 3]],
            [
                'nextval(:values__0__0), geometry::STGeomFromText(:values__1__0, :values__1__1), now(), :values__3',
                [
                    ':values__0__0' => PdoParameter::str('serial_seq'),
                    ':values__1__0' => PdoParameter::str('POINT(1 1)'),
                    ':values__1__1' => PdoParameter::int(0),
                    ':values__3'    => PdoParameter::int(1)
                ],
                'values',
                [Expression::of('nextval({0})', 'serial_seq'), Expression::of('geometry::STGeomFromText({0}, {1})', 'POINT(1 1)', 0), Expression::of('now()'), 1]
            ],
            ['now()', [], 'key', Expression::of('now()')],
            ['GeomFromText(:key__0)', [':key__0' => PdoParameter::str('POINT(1 1)')], 'key', Expression::of('GeomFromText({0})', 'POINT(1 1)')],
        ];
    }

    /**
     * @dataProvider dataConvertParams
     */
    public function test_convertParam(string $expect_sql, array $expect_params, string $key, $value)
    {
        $this->eachDb(function (Database $db) use ($expect_sql, $expect_params, $key, $value) {
            $param = $db->compiler()->convertParam($key, $value);
            $this->assertEquals($expect_sql, $param->sql());
            $this->assertEquals($expect_params, $param->params());
        });
    }
}
