<?php
namespace Rebet\Tests\Database\Compiler;

use Rebet\Database\Compiler\BuiltinCompiler;
use Rebet\Database\Compiler\Compiler;
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
        $this->compiler = new BuiltinCompiler();
        DateTime::setTestNow('2001-02-03 04:05:06');
    }

    protected function tables(string $db_name) : array
    {
        return [
            'sqlite' => [
                'users' => <<<EOS
                    CREATE TABLE IF NOT EXISTS users (
                        user_id INTEGER PRIMARY KEY,
                        name TEXT NOT NULL,
                        gender INTEGER NOT NULL,
                        birthday TEXT NOT NULL,
                        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at TEXT
                    );
EOS
            ],
            'mysql' => [
                'users' => <<<EOS
                    CREATE TABLE IF NOT EXISTS users (
                        user_id INTEGER PRIMARY KEY,
                        name TEXT NOT NULL,
                        gender INTEGER NOT NULL,
                        birthday DATE NOT NULL,
                        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at DATETIME
                    );
EOS
            ],
            'pgsql' => [
                'users' => <<<EOS
                    CREATE TABLE IF NOT EXISTS users (
                        user_id INTEGER PRIMARY KEY,
                        name TEXT NOT NULL,
                        gender INTEGER NOT NULL,
                        birthday DATE NOT NULL,
                        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP
                    );
EOS
            ],
        ][$db_name] ?? [];
    }

    protected function records(string $db_name, string $table_name) : array
    {
        return [
            'users' => [
                ['user_id' => 1 , 'name' => 'Elody Bode III'        , 'gender' => 2, 'birthday' => '1990-01-08'],
                ['user_id' => 2 , 'name' => 'Alta Hegmann'          , 'gender' => 1, 'birthday' => '2003-02-16'],
                ['user_id' => 3 , 'name' => 'Damien Kling'          , 'gender' => 1, 'birthday' => '1992-10-17'],
                ['user_id' => 4 , 'name' => 'Odie Kozey'            , 'gender' => 1, 'birthday' => '2008-03-23'],
                ['user_id' => 5 , 'name' => 'Shea Douglas'          , 'gender' => 1, 'birthday' => '1988-04-01'],
                ['user_id' => 6 , 'name' => 'Khalil Hickle'         , 'gender' => 2, 'birthday' => '2013-10-03'],
                ['user_id' => 7 , 'name' => 'Kali Hilll'            , 'gender' => 1, 'birthday' => '2016-08-01'],
                ['user_id' => 8 , 'name' => 'Kari Kub'              , 'gender' => 2, 'birthday' => '1984-10-21'],
                ['user_id' => 9 , 'name' => 'Rodger Weimann'        , 'gender' => 1, 'birthday' => '1985-03-21'],
                ['user_id' => 10, 'name' => 'Nicholaus O\'Conner'   , 'gender' => 1, 'birthday' => '2012-01-29'],
                ['user_id' => 11, 'name' => 'Troy Smitham'          , 'gender' => 2, 'birthday' => '1996-01-21'],
                ['user_id' => 12, 'name' => 'Kraig Grant'           , 'gender' => 2, 'birthday' => '1987-01-06'],
                ['user_id' => 13, 'name' => 'Demarcus Bashirian Jr.', 'gender' => 2, 'birthday' => '2014-12-21'],
                ['user_id' => 14, 'name' => 'Percy DuBuque'         , 'gender' => 2, 'birthday' => '1990-11-25'],
                ['user_id' => 15, 'name' => 'Delpha Weber'          , 'gender' => 2, 'birthday' => '2006-01-29'],
                ['user_id' => 16, 'name' => 'Marquise Waters'       , 'gender' => 2, 'birthday' => '1989-08-26'],
                ['user_id' => 17, 'name' => 'Jade Stroman'          , 'gender' => 1, 'birthday' => '2013-08-06'],
                ['user_id' => 18, 'name' => 'Citlalli Jacobs I'     , 'gender' => 2, 'birthday' => '1983-02-09'],
                ['user_id' => 19, 'name' => 'Dannie Rutherford'     , 'gender' => 1, 'birthday' => '1982-07-07'],
                ['user_id' => 20, 'name' => 'Dayton Herzog'         , 'gender' => 2, 'birthday' => '2014-11-24'],
                ['user_id' => 21, 'name' => 'Ms. Zoe Hirthe'        , 'gender' => 2, 'birthday' => '1997-02-27'],
                ['user_id' => 22, 'name' => 'Kaleigh Kassulke'      , 'gender' => 2, 'birthday' => '2011-01-23'],
                ['user_id' => 23, 'name' => 'Deron Macejkovic'      , 'gender' => 1, 'birthday' => '2008-06-18'],
                ['user_id' => 24, 'name' => 'Mr. Aisha Quigley'     , 'gender' => 2, 'birthday' => '2007-08-29'],
                ['user_id' => 25, 'name' => 'Eugenia Friesen II'    , 'gender' => 2, 'birthday' => '1999-12-19'],
                ['user_id' => 26, 'name' => 'Wyman Jaskolski'       , 'gender' => 2, 'birthday' => '2010-07-06'],
                ['user_id' => 27, 'name' => 'Naomi Batz'            , 'gender' => 2, 'birthday' => '1980-03-06'],
                ['user_id' => 28, 'name' => 'Miss Bud Koepp'        , 'gender' => 1, 'birthday' => '2014-10-22'],
                ['user_id' => 29, 'name' => 'Ms. Harmon Blick'      , 'gender' => 1, 'birthday' => '1987-03-20'],
                ['user_id' => 30, 'name' => 'Pinkie Kiehn'          , 'gender' => 1, 'birthday' => '2002-01-06'],
                ['user_id' => 31, 'name' => 'Harmony Feil'          , 'gender' => 2, 'birthday' => '2007-11-03'],
                ['user_id' => 32, 'name' => 'River Pagac'           , 'gender' => 2, 'birthday' => '1980-11-20'],
            ],
        ][$table_name] ?? [];
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
                [':gender' => PdoParameter::int(1)],
                "SELECT * FROM user WHERE gender = :gender",
                ['user_id' => 'desc'],
                ['gender'  => 1]
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user WHERE gender = :gender ORDER BY user_id DESC",
                [':gender' => PdoParameter::int(1)],
                "SELECT * FROM user WHERE gender = :gender",
                ['user_id' => 'desc'],
                ['gender'  => Gender::MALE()]
            ],
            [
                ['sqlite', 'mysql'],
                "SELECT * FROM user WHERE gender = :gender AND create_at > :create_at",
                [':gender' => PdoParameter::int(1), ':create_at' => PdoParameter::str('2001-02-03 04:05:06')],
                "SELECT * FROM user WHERE gender = :gender AND create_at > :create_at",
                null,
                ['gender'  => Gender::MALE(), 'create_at' => DateTime::now()]
            ],
            [
                ['pgsql'],
                "SELECT * FROM user WHERE gender = :gender AND create_at > :create_at",
                [':gender' => PdoParameter::int(1), ':create_at' => PdoParameter::str('2001-02-03 04:05:06+0000')],
                "SELECT * FROM user WHERE gender = :gender AND create_at > :create_at",
                null,
                ['gender'  => Gender::MALE(), 'create_at' => DateTime::now()]
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user WHERE gender IN (:gender__0, :gender__1)",
                [':gender__0' => PdoParameter::int(1), ':gender__1' => PdoParameter::int(2)],
                "SELECT * FROM user WHERE gender IN (:gender)",
                null,
                ['gender' => [1, 2]]
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user WHERE gender IN (:gender__0)",
                [':gender__0' => PdoParameter::int(1)],
                "SELECT * FROM user WHERE gender IN (:gender)",
                null,
                ['gender' => [1]]
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user WHERE gender IN (:gender__0, :gender__1)",
                [':gender__0' => PdoParameter::int(1), ':gender__1' => PdoParameter::int(2)],
                "SELECT * FROM user WHERE gender IN (:gender)",
                null,
                ['gender' => [Gender::MALE(), Gender::FEMALE()]]
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "INSERT INTO foo VALUES (:values__0, :values__1, :values__2)",
                [':values__0' => PdoParameter::int(1), ':values__1' => PdoParameter::null(), ':values__2' => PdoParameter::str('a')],
                "INSERT INTO foo VALUES (:values)",
                null,
                ['values' => [1, null, 'a']]
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "INSERT INTO foo VALUES (:values__0, :values__1, :values__2)",
                [':values__0' => PdoParameter::int(1), ':values__1' => PdoParameter::null(), ':values__2' => PdoParameter::str('a')],
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
                [':cursor__0' => PdoParameter::int(21)],
                "SELECT * FROM user",
                $order_by = ['user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(3),   // {[3]} (4)
                Cursor::create($order_by, $pager, ['user_id' => 21], null)
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user WHERE birthday < CURRENT_TIMESTAMP - INTERVAL 20 YEAR AND ((gender = :cursor__0 AND user_id >= :cursor__1) OR (gender < :cursor__0)) ORDER BY gender DESC, user_id ASC LIMIT 11 OFFSET 0",
                [':cursor__0' => PdoParameter::int(2), ':cursor__1' => PdoParameter::int(21)],
                "SELECT * FROM user WHERE birthday < CURRENT_TIMESTAMP - INTERVAL 20 YEAR",
                $order_by = ['gender' => 'desc', 'user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(3),   // {[3]} (4)
                Cursor::create($order_by, $pager, ['gender' => Gender::FEMALE(), 'user_id' => 21], null)
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user HAVING foo = 1 AND ((user_id >= :cursor__0)) ORDER BY user_id ASC LIMIT 11 OFFSET 0",
                [':cursor__0' => PdoParameter::int(21)],
                "SELECT * FROM user HAVING foo = 1",
                $order_by = ['user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(3),   // {[3]} (4)
                Cursor::create($order_by, $pager, ['user_id' => 21], null)
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user WHERE foo = 1 HAVING bar = 1 AND ((user_id >= :cursor__0)) ORDER BY user_id ASC LIMIT 11 OFFSET 0",
                [':cursor__0' => PdoParameter::int(21)],
                "SELECT * FROM user WHERE foo = 1 HAVING bar = 1",
                $order_by = ['user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(3),   // {[3]} (4)
                Cursor::create($order_by, $pager, ['user_id' => 21], null)
            ],
            [
                ['sqlite', 'mysql'],
                "SELECT * FROM user WHERE (gender = :cursor__0 AND update_at = :cursor__1 AND user_id >= :cursor__2) OR (gender = :cursor__0 AND update_at < :cursor__1) OR (gender < :cursor__0) ORDER BY gender DESC, update_at DESC, user_id ASC LIMIT 11 OFFSET 0",
                [':cursor__0' => PdoParameter::int(2), ':cursor__1' => PdoParameter::str('2000-12-18 12:34:56'), ':cursor__2' => PdoParameter::int(21)],
                "SELECT * FROM user",
                $order_by = ['gender' => 'desc', 'update_at' => 'desc', 'user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(3),   // {[3]} (4)
                Cursor::create($order_by, $pager, ['gender' => Gender::FEMALE(), 'update_at' => DateTime::createDateTime('2000-12-18 12:34:56'), 'user_id' => 21], null)
            ],
            [
                ['pgsql'],
                "SELECT * FROM user WHERE (gender = :cursor__0 AND update_at = :cursor__1 AND user_id >= :cursor__2) OR (gender = :cursor__0 AND update_at < :cursor__1) OR (gender < :cursor__0) ORDER BY gender DESC, update_at DESC, user_id ASC LIMIT 11 OFFSET 0",
                [':cursor__0' => PdoParameter::int(2), ':cursor__1' => PdoParameter::str('2000-12-18 12:34:56+0000'), ':cursor__2' => PdoParameter::int(21)],
                "SELECT * FROM user",
                $order_by = ['gender' => 'desc', 'update_at' => 'desc', 'user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(3),   // {[3]} (4)
                Cursor::create($order_by, $pager, ['gender' => Gender::FEMALE(), 'update_at' => DateTime::createDateTime('2000-12-18 12:34:56'), 'user_id' => 21], null)
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user WHERE (gender = :cursor__0 AND user_id >= :cursor__1) OR (gender < :cursor__0) ORDER BY gender DESC, user_id ASC LIMIT 11 OFFSET 20",
                [':cursor__0' => PdoParameter::int(2), ':cursor__1' => PdoParameter::int(21)],
                "SELECT * FROM user",
                $order_by = ['gender' => 'desc', 'user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(5),   // {3} 4 [5] (6)
                Cursor::create($order_by, $pager->prev(2), ['gender' => Gender::FEMALE(), 'user_id' => 21], null)
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user WHERE (gender = :cursor__0 AND user_id >= :cursor__1) OR (gender < :cursor__0) ORDER BY gender ASC, user_id DESC LIMIT 11 OFFSET 10",
                [':cursor__0' => PdoParameter::int(2), ':cursor__1' => PdoParameter::int(21)],
                "SELECT * FROM user",
                $order_by = ['gender' => 'desc', 'user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(5),   // [5] (6) {7} ?
                Cursor::create($order_by, $pager->next(2), ['gender' => Gender::FEMALE(), 'user_id' => 21], null)
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user WHERE (gender = :cursor__0 AND user_id <= :cursor__1) OR (gender > :cursor__0) ORDER BY gender DESC, user_id ASC LIMIT 11 OFFSET 20",
                [':cursor__0' => PdoParameter::int(2), ':cursor__1' => PdoParameter::int(21)],
                "SELECT * FROM user",
                $order_by = ['gender' => 'desc', 'user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(3),   // [3] (4) 5 6 7 8 9 {10} ? (near by first)
                Cursor::create($order_by, $pager->next(7), ['gender' => Gender::FEMALE(), 'user_id' => 21], null)
            ],
            [
                ['sqlite', 'mysql', 'pgsql'],
                "SELECT * FROM user WHERE (user_id >= :cursor__0) ORDER BY user_id DESC LIMIT 11 OFFSET 10",
                [':cursor__0' => PdoParameter::int(21)],
                "SELECT * FROM user",
                $order_by = ['user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(9)->eachSide(5),   // [9] (10) {11} 12 13 14 15
                Cursor::create($order_by, $pager->next(2), ['user_id' => 21], 4)
            ],
            [
                ['sqlite', 'mysql'],
                "SELECT U.*, A.article_id, A.create_at AS article_create_at FROM user AS U INNER JOIN article AS A USING(user_id) WHERE U.user_id = 1 AND ((A.create_at = :cursor__0 AND article_id >= :cursor__1) OR (A.create_at < :cursor__0)) ORDER BY article_create_at DESC, article_id ASC LIMIT 11 OFFSET 0",
                [':cursor__0' => PdoParameter::str('2001-02-03 04:05:06'), ':cursor__1' => PdoParameter::int(21)],
                "SELECT U.*, A.article_id, A.create_at AS article_create_at FROM user AS U INNER JOIN article AS A USING(user_id) WHERE U.user_id = 1",
                $order_by = ['article_create_at' => 'desc', 'article_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(3),   // {[3]} (4)
                Cursor::create($order_by, $pager, ['article_create_at' => DateTime::now(), 'article_id' => 21], null)
            ],
            [
                ['pgsql'],
                "SELECT U.*, A.article_id, A.create_at AS article_create_at FROM user AS U INNER JOIN article AS A USING(user_id) WHERE U.user_id = 1 AND ((A.create_at = :cursor__0 AND article_id >= :cursor__1) OR (A.create_at < :cursor__0)) ORDER BY article_create_at DESC, article_id ASC LIMIT 11 OFFSET 0",
                [':cursor__0' => PdoParameter::str('2001-02-03 04:05:06+0000'), ':cursor__1' => PdoParameter::int(21)],
                "SELECT U.*, A.article_id, A.create_at AS article_create_at FROM user AS U INNER JOIN article AS A USING(user_id) WHERE U.user_id = 1",
                $order_by = ['article_create_at' => 'desc', 'article_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(3),   // {[3]} (4)
                Cursor::create($order_by, $pager, ['article_create_at' => DateTime::now(), 'article_id' => 21], null)
            ],
            [
                ['sqlite', 'mysql'],
                "SELECT *, COALESCE(update_at, create_at) as change_at FROM user WHERE (COALESCE(update_at,create_at) = :cursor__0 AND user_id >= :cursor__1) OR (COALESCE(update_at,create_at) > :cursor__0) ORDER BY change_at ASC, user_id ASC LIMIT 11 OFFSET 0",
                [':cursor__0' => PdoParameter::str('2001-02-03 04:05:06'), ':cursor__1' => PdoParameter::int(21)],
                "SELECT *, COALESCE(update_at, create_at) as change_at FROM user",
                $order_by = ['change_at' => 'asc', 'user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(3),   // {[3]} (4)
                Cursor::create($order_by, $pager, ['change_at' => DateTime::now(), 'user_id' => 21], null)
            ],
            [
                ['pgsql'],
                "SELECT *, COALESCE(update_at, create_at) as change_at FROM user WHERE (COALESCE(update_at,create_at) = :cursor__0 AND user_id >= :cursor__1) OR (COALESCE(update_at,create_at) > :cursor__0) ORDER BY change_at ASC, user_id ASC LIMIT 11 OFFSET 0",
                [':cursor__0' => PdoParameter::str('2001-02-03 04:05:06+0000'), ':cursor__1' => PdoParameter::int(21)],
                "SELECT *, COALESCE(update_at, create_at) as change_at FROM user",
                $order_by = ['change_at' => 'asc', 'user_id' => 'asc'],
                null,
                $pager = Pager::resolve()->page(3),   // {[3]} (4)
                Cursor::create($order_by, $pager, ['change_at' => DateTime::now(), 'user_id' => 21], null)
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
            if (!($db = $this->connect($db_kind))) {
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
