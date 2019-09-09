<?php
namespace Rebet\Tests\Database\Compiler;

use Exception;
use PHPUnit\Framework\AssertionFailedError;
use Rebet\Common\Arrays;
use Rebet\Config\Config;
use Rebet\Database\Compiler\BuiltinCompiler;
use Rebet\Database\Converter\BuiltinConverter;
use Rebet\Database\Dao;
use Rebet\Database\Database;
use Rebet\Database\Driver\PdoDriver;
use Rebet\Database\Exception\DatabaseException;
use Rebet\Database\Pagination\Cursor;
use Rebet\Database\Pagination\Pager;
use Rebet\Database\Pagination\Paginator;
use Rebet\Database\PdoParameter;
use Rebet\DateTime\Date;
use Rebet\DateTime\DateTime;
use Rebet\Tests\Mock\Article;
use Rebet\Tests\Mock\Enum\Gender;
use Rebet\Tests\Mock\User;
use Rebet\Tests\RebetDatabaseTestCase;

class DatabaseTest extends RebetDatabaseTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        DateTime::setTestNow('2001-02-03 04:05:06');
    }

    protected function tables(string $db_name) : array
    {
        $db_name = $db_name === 'main' ? 'sqlite' : $db_name ;
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
                ,
                'articles' => <<<EOS
                    CREATE TABLE IF NOT EXISTS articles (
                        article_id INTEGER PRIMARY KEY AUTOINCREMENT,
                        user_id INTEGER NOT NULL,
                        subject TEXT NOT NULL,
                        body TEXT NOT NULL,
                        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at TEXT
                    );
EOS
                ,
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
                ,
                'articles' => <<<EOS
                    CREATE TABLE IF NOT EXISTS articles (
                        article_id INTEGER PRIMARY KEY AUTO_INCREMENT,
                        user_id INTEGER NOT NULL,
                        subject VARCHAR(30) NOT NULL,
                        body TEXT NOT NULL,
                        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at DATETIME
                    );
EOS
                ,
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
                ,
                'articles' => <<<EOS
                    CREATE TABLE IF NOT EXISTS articles (
                        article_id SERIAL,
                        user_id INTEGER NOT NULL,
                        subject VARCHAR(30) NOT NULL,
                        body TEXT NOT NULL,
                        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP
                    );
EOS
                ,
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

    public function test_name()
    {
        foreach (array_keys(Dao::config('dbs')) as $name) {
            $this->assertSame($name, Dao::db($name)->name());
        }
    }

    public function test_driverName()
    {
        $this->assertSame('sqlite', Dao::db()->driverName());
        $this->assertSame('sqlite', Dao::db('main')->driverName());
        $this->assertSame('sqlite', Dao::db('sqlite')->driverName());
        $this->assertSame('mysql', Dao::db('mysql')->driverName());
        $this->assertSame('pgsql', Dao::db('pgsql')->driverName());
    }

    public function test_serverVersion()
    {
        foreach (array_keys(Dao::config('dbs')) as $name) {
            $this->assertRegExp('/[0-9]+\.[0-9]+(\.[0-9]+)?/', Dao::db($name)->serverVersion());
        }
    }

    public function test_clientVersion()
    {
        foreach (array_keys(Dao::config('dbs')) as $name) {
            $this->assertRegExp('/[0-9]+\.[0-9]+(\.[0-9]+)?/', Dao::db($name)->clientVersion());
        }
    }

    public function test_driver()
    {
        foreach (array_keys(Dao::config('dbs')) as $name) {
            $this->assertInstanceOf(PdoDriver::class, Dao::db($name)->driver());
        }
    }

    public function test_compiler()
    {
        foreach (array_keys(Dao::config('dbs')) as $name) {
            $this->assertInstanceOf(BuiltinCompiler::class, Dao::db($name)->compiler());
        }
    }

    public function test_converter()
    {
        foreach (array_keys(Dao::config('dbs')) as $name) {
            $this->assertInstanceOf(BuiltinConverter::class, Dao::db($name)->converter());
        }
    }

    public function test_logAndDebug()
    {
        $name     = 'sqlite';
        $sql      = "SELECT * FROM user WHERE user_id = :user_id";
        $params   = [':user_id' => 1];
        $emulated = "/* Emulated SQL */ SELECT * FROM user WHERE user_id = '1'";

        $en = null;
        $es = null;
        $ep = null;
        Dao::clear();
        Config::application([
            Dao::class => [
                'dbs' => [
                    'sqlite' => [
                        'log_handler'      => function (string $n, string $s, array $p = []) use (&$en, &$es, &$ep) {
                            $en = $n;
                            $es = $s;
                            $ep = $p;
                        },
                        'emulated_sql_log' => false,
                        'debug'            => true,
                    ]
                ]
            ]
        ]);

        Dao::db('sqlite')->log($sql, $params);
        $this->assertSame($en, $name);
        $this->assertSame($es, $sql);
        $this->assertSame($ep, $params);

        $en = null;
        $es = null;
        $ep = null;
        Dao::db('sqlite')->debug(false)->log($sql, $params);
        $this->assertSame($en, null);
        $this->assertSame($es, null);
        $this->assertSame($ep, null);

        $en = null;
        $es = null;
        $ep = null;
        Dao::db('sqlite')->debug(true, true)->log($sql, $params);
        $this->assertSame($en, $name);
        $this->assertSame($es, $emulated);
        $this->assertSame($ep, []);

        $en = null;
        $es = null;
        $ep = null;
        Dao::db('sqlite')->debug(false)->log($sql, $params);
        $this->assertSame($en, null);
        $this->assertSame($es, null);
        $this->assertSame($ep, null);
    }

    public function test_exception()
    {
        $sql      = "bogus SELECT * FROM user WHERE user_id = :user_id";
        $params   = [':user_id' => 1];
        $error    = ['HY000', 1, 'near "bogus": syntax error'];

        $exception = Dao::db('sqlite')->exception($error, $sql, $params);
        $this->assertInstanceOf(DatabaseException::class, $exception);
    }

    public function test_convertToPdo()
    {
        $this->assertInstanceOf(PdoParameter::class, Dao::db('sqlite')->convertToPdo(123));
    }

    public function test_convertToPhp()
    {
        $this->assertEquals(123, Dao::db('sqlite')->convertToPhp(123));
        $this->assertEquals(new Date('2001-02-03'), Dao::db('sqlite')->convertToPhp('2001-02-03', [], Date::class));
        $this->assertEquals('2001-02-03', Dao::db('sqlite')->convertToPhp('2001-02-03', ['native_type' => 'string']));
        $this->assertEquals(new Date('2001-02-03'), Dao::db('mysql')->convertToPhp('2001-02-03', ['native_type' => 'date']));
    }

    public function test_beginAndSavepointAndCommitAndRollback()
    {
        $this->eachDb(function (Database $db) {
            $this->assertInstanceOf(Database::class, $db->begin(), "on {$db->name()}");

            $user = User::find(1);
            $this->assertSame('Elody Bode III', $user->name);

            $user->name = 'Carole Stanley';
            $user->update();

            $user = User::find(1);
            $this->assertSame('Carole Stanley', $user->name);

            $db->rollback();
            $db->begin();

            $user = User::find(1);
            $this->assertSame('Elody Bode III', $user->name);

            $user->name = 'Carole Stanley';
            $user->update();

            $user = User::find(1);
            $this->assertSame('Carole Stanley', $user->name);

            $db->savepoint('carole');

            $user->name = 'Dan Montgomery';
            $user->update();

            $user = User::find(1);
            $this->assertSame('Dan Montgomery', $user->name);

            $db->savepoint('dan');

            $user->name = 'Foo Bar';
            $user->update();

            $user = User::find(1);
            $this->assertSame('Foo Bar', $user->name);

            $db->rollback('dan');

            $user = User::find(1);
            $this->assertSame('Dan Montgomery', $user->name);

            $db->rollback('carole');

            $user = User::find(1);
            $this->assertSame('Carole Stanley', $user->name);

            $db->commit();
            $db->rollback();

            $user = User::find(1);
            $this->assertSame('Carole Stanley', $user->name);
        });
    }

    public function test_rollbackQuiet()
    {
        Dao::db()->rollback();
        $this->assertTrue(true);
    }

    /**
     * @expectedException \PDOException
     * @expectedExceptionMessage There is no active transaction
     */
    public function test_rollbackNotQuiet()
    {
        Dao::db()->rollback(null, false);
    }

    public function test_transaction()
    {
        $this->eachDb(function (Database $db) {
            try {
                $db->transaction(function (Database $db) {
                    $user = User::find(1);
                    $this->assertEquals('Elody Bode III', $user->name);

                    $user->name = 'Carole Stanley';
                    $user->update();

                    $user = User::find(1);
                    $this->assertEquals('Carole Stanley', $user->name);

                    throw new Exception("Something error occurred.");
                });
            } catch (AssertionFailedError $e) {
                throw $e;
            } catch (Exception $e) {
                $this->assertEquals("Something error occurred.", $e->getMessage());
            }

            $user = User::find(1);
            $this->assertEquals('Elody Bode III', $user->name);

            $db->transaction(function (Database $db) {
                $user = User::find(1);
                $this->assertEquals('Elody Bode III', $user->name);

                $user->name = 'Carole Stanley';
                $user->update();

                $user = User::find(1);
                $this->assertEquals('Carole Stanley', $user->name);
            });

            $user = User::find(1);
            $this->assertEquals('Carole Stanley', $user->name);
        });
    }

    public function test_lastInsertId()
    {
        $this->eachDb(function (Database $db) {
            $article          = new Article();
            $article->user_id = 1;
            $article->subject = 'foo';
            $article->body    = 'bar';
            $article->create();

            $this->assertSame('1', $db->lastInsertId());
            $this->assertSame('1', $article->article_id);

            $article          = new Article();
            $article->user_id = 1;
            $article->subject = 'baz';
            $article->body    = 'qux';
            $article->create();

            $this->assertSame('2', $db->lastInsertId());
            $this->assertSame('2', $article->article_id);

            // $article             = new Article();
            // $article->article_id = 5;
            // $article->user_id    = 1;
            // $article->subject    = 'quux';
            // $article->body       = 'quuux';
            // $article->create();

            // $this->assertSame('5', $db->lastInsertId());
            // $this->assertSame('5', $article->article_id);
        });
    }

    public function dataQueries() : array
    {
        $this->setUp();
        return [
            [[1], 'user_id', "SELECT * FROM users WHERE user_id = 1"],
            [[2, 3, 4, 5, 7, 9, 10, 17, 19, 23, 28, 29, 30], 'user_id', "SELECT * FROM users WHERE gender = 1"],
            [[7, 28, 17, 10, 23, 4, 2, 30, 3, 5, 29, 9, 19], 'user_id', "SELECT * FROM users WHERE gender = 1 ORDER BY birthday DESC"],
            [[7, 28, 17, 10, 23, 4, 2, 30, 3, 5, 29, 9, 19], 'user_id', "SELECT * FROM users WHERE gender = :gender ORDER BY birthday DESC", ['gender' => Gender::MALE()]],

            [[1], 'user_id', "SELECT * FROM users WHERE user_id = :user_id AND user_id = :user_id", ['user_id' => 1]],
            [[1, 3, 5], 'user_id', "SELECT * FROM users WHERE user_id IN (:user_id)", ['user_id' => [1, 3, 5]]],
            [[1, 3, 5], 'user_id', "SELECT * FROM users WHERE user_id IN (:user_id) AND user_id IN (:user_id)", ['user_id' => [1, 3, 5]]],
        ];
    }

    /**
     * @dataProvider dataQueries
     */
    public function test_query($expect, $col, $sql, $params = [])
    {
        $this->eachDb(function (Database $db) use ($expect, $col, $sql, $params) {
            $rs = $db->query($sql, $params)->allOf($col);
            $this->assertSame($expect, $rs->toArray());
        });
    }

    public function test_execute()
    {
        $this->eachDb(function (Database $db) {
            $user = User::find(3);
            $this->assertSame('Damien Kling', $user->name);

            $this->assertSame(1, $db->execute("UPDATE users SET name = :name WHERE user_id = :user_id", ['name' => 'foo', 'user_id' => 3]));

            $user = User::find(3);
            $this->assertSame('foo', $user->name);

            $this->assertSame(13, $db->count("SELECT * FROM users WHERE gender = 1"));
            $this->assertSame(13, $db->execute("UPDATE users SET gender = 2 WHERE gender = 1"));
            $this->assertSame(0, $db->count("SELECT * FROM users WHERE gender = 1"));

            $this->assertSame(1, $db->execute("INSERT INTO users (user_id, name, gender, birthday) VALUES (:values)", [
                'values' => ['user_id' => 33, 'name' => 'Insert', 'gender' => Gender::MALE(), 'birthday' => Date::createDateTime('1976-04-23')]
            ]));

            $this->assertSame(1, $db->count("SELECT * FROM users WHERE gender = 1"));
        });
    }

    public function test_select()
    {
        DateTime::setTestNow('2019-09-01');
        $this->eachDb(function (Database $db) {
            $users = $db->select("SELECT * FROM users WHERE gender = 1");
            $this->assertEquals([2, 3, 4, 5, 7, 9, 10, 17, 19, 23, 28, 29, 30], Arrays::pluck($users->toArray(), 'user_id'));

            $users = $db->select("SELECT * FROM users WHERE gender = 1", ['user_id' => 'desc']);
            $this->assertEquals([30, 29, 28, 23, 19, 17, 10, 9, 7, 5, 4, 3, 2], Arrays::pluck($users->toArray(), 'user_id'));

            $users = $db->select("SELECT * FROM users WHERE gender = :gender", ['user_id' => 'desc'], ['gender' => Gender::MALE()]);
            $this->assertEquals([30, 29, 28, 23, 19, 17, 10, 9, 7, 5, 4, 3, 2], Arrays::pluck($users->toArray(), 'user_id'));

            $users = $db->select("SELECT * FROM users WHERE gender = :gender", ['user_id' => 'desc'], ['gender' => Gender::MALE()], User::class);
            $this->assertEquals([17, 32, 4, 11, 37, 6, 7, 34, 3, 31, 11, 26, 16], array_map(function ($v) {
                $this->assertInstanceOf(User::class, $v);
                return $v->age();
            }, $users->toArray()));
        });
    }

    public function dataPaginates() : array
    {
        $this->setUp();
        return [
            // 7, 13, 20, 28, 6, 17, 10, 22, 26, 23, 4, 31, 24, 15, 2, 30, 25, 21, 11, 3, 14, 1, 16, 5, 29, 12, 9, 8, 18, 19, 32, 27 : birthday DESC

            // 7, 28,  17, 10, 23, 4, 2, 30, 3, 5, 29, 9, 19 : birthday DESC, gender = 1
            [ [7, 28, 17]           , null, 1, null, "SELECT * FROM users WHERE gender = :gender", ['birthday' => 'desc'], ['gender' => Gender::MALE()], Pager::resolve()->size(3)],
            [            [10, 23, 4], null, 1, null, "SELECT * FROM users WHERE gender = :gender", ['birthday' => 'desc'], ['gender' => Gender::MALE()], Pager::resolve()->size(3)->page(2)],

            // 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32 : user_id ASC
            [          [4, 5, 6]         , null, 3, null, "SELECT * FROM users", ['user_id' => 'asc'], [], Pager::resolve()->size(3)->page(2)->eachSide(2)],
            [                   [7, 8, 9], null, 2, null, "SELECT * FROM users", ['user_id' => 'asc'], [], Pager::resolve()->size(3)->page(3)->eachSide(2)],
            [                   [7, 8, 9],   32, 8, null, "SELECT * FROM users", ['user_id' => 'asc'], [], Pager::resolve()->size(3)->page(3)->eachSide(2)->needTotal(true)],

            [[16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30], null, 1, null, "SELECT * FROM users", ['user_id' => 'asc'], [], Pager::resolve()->size(15)->page(2)->eachSide(2)],

            //   30, 29, 28, 23, 19, 17, 10, 9, 7, 5, 4, 3, 2, 32, 31, 27, 26, 25, 24, 22, 21, 20, 18, 16, 15, 14, 13, 12, 11, 8, 6, 1 : gender ASC, user_id DESC
            [
                [30, 29, 28], null, 1, null,
                "SELECT * FROM users", $order_by = ['gender' => 'asc', 'user_id' => 'desc'], [], $pager = Pager::resolve()->size(3)->page(-1)
            ],
            [
                [30, 29, 28], null, 1, null,
                "SELECT * FROM users", $order_by = ['gender' => 'asc', 'user_id' => 'desc'], [], $pager = Pager::resolve()->size(3)
            ],
            [
                [            23, 19, 17], null, 1, null,
                "SELECT * FROM users", $order_by = ['gender' => 'asc', 'user_id' => 'desc'], [], $pager = Pager::resolve()->size(3)->page(2)
            ],
            [
                [                        10, 9, 7], null, 1, null,
                "SELECT * FROM users", $order_by = ['gender' => 'asc', 'user_id' => 'desc'], [], $pager = Pager::resolve()->size(3)->page(3)
            ],
            [
                [                                  5, 4, 3], null, 1, null,
                "SELECT * FROM users", $order_by = ['gender' => 'asc', 'user_id' => 'desc'], [], $pager = Pager::resolve()->size(3)->page(4)
            ],
            [
                [                                                                                                      12, 11, 8], null, 1, null,
                "SELECT * FROM users", $order_by = ['gender' => 'asc', 'user_id' => 'desc'], [], $pager = Pager::resolve()->size(3)->page(10)
            ],
            [
                [                                                                                                                 6, 1], null, 0, null,
                "SELECT * FROM users", $order_by = ['gender' => 'asc', 'user_id' => 'desc'], [], $pager = Pager::resolve()->size(3)->page(11)
            ],
            [
                [], null, 0, null,
                "SELECT * FROM users", $order_by = ['gender' => 'asc', 'user_id' => 'desc'], [], $pager = Pager::resolve()->size(3)->page(12)
            ],

            //   30, 29, 28, 23, 19, 17, 10, 9, 7, 5, 4, 3, 2, 32, 31, 27, 26, 25, 24, 22, 21, 20, 18, 16, 15, 14, 13, 12, 11, 8, 6, 1 : gender ASC, user_id DESC
            [
                [30, 29, 28], null, 1,
                Cursor::create(
                    $order_by = ['gender' => 'asc', 'user_id' => 'desc'],
                    Pager::resolve()->cursor('unittest')->size(3)->page(2),
                    ['gender' => 1, 'user_id' => 23],
                    0
                ),
                "SELECT * FROM users", $order_by, [], Pager::resolve()->cursor('unittest')->size(3)->page(1),
                null
            ],
            [
                [            23, 19, 17], null, 1,
                Cursor::create(
                    $order_by = ['gender' => 'asc', 'user_id' => 'desc'],
                    Pager::resolve()->cursor('unittest')->size(3)->page(3),
                    ['gender' => 1, 'user_id' => 10],
                    0
                ),
                "SELECT * FROM users", $order_by, [], Pager::resolve()->cursor('unittest')->size(3)->page(2),
                Cursor::create(
                    $order_by,
                    Pager::resolve()->cursor('unittest')->size(3)->page(2),
                    ['gender' => 1, 'user_id' => 23],
                    0
                ),
            ],
            [
                [                                  5, 4, 3], null, 1,
                Cursor::create(
                    $order_by = ['gender' => 'asc', 'user_id' => 'desc'],
                    Pager::resolve()->cursor('unittest')->size(3)->page(5),
                    ['gender' => 1, 'user_id' => 2],
                    0
                ),
                "SELECT * FROM users", $order_by, [], Pager::resolve()->cursor('unittest')->size(3)->page(4),
                Cursor::create(
                    $order_by,
                    Pager::resolve()->cursor('unittest')->size(3)->page(3),
                    ['gender' => 1, 'user_id' => 10],
                    0
                ),
            ],
            [
                [                                                                                                      12, 11, 8], null, 1,
                Cursor::create(
                    $order_by = ['gender' => 'asc', 'user_id' => 'desc'],
                    Pager::resolve()->cursor('unittest')->size(3)->page(11),
                    ['gender' => 2, 'user_id' => 6],
                    0
                ),
                "SELECT * FROM users", $order_by, [], Pager::resolve()->cursor('unittest')->size(3)->page(10),
                Cursor::create(
                    $order_by,
                    Pager::resolve()->cursor('unittest')->size(3)->page(5),
                    ['gender' => 1, 'user_id' => 2],
                    0
                ),
            ],
            [
                [                                                                                                                 6, 1], null, 0,
                Cursor::create(
                    $order_by = ['gender' => 'asc', 'user_id' => 'desc'],
                    Pager::resolve()->cursor('unittest')->size(3)->page(11),
                    ['gender' => 2, 'user_id' => 6],
                    0
                ),
                "SELECT * FROM users", $order_by, [], Pager::resolve()->cursor('unittest')->size(3)->page(11),
                Cursor::create(
                    $order_by,
                    Pager::resolve()->cursor('unittest')->size(3)->page(11),
                    ['gender' => 2, 'user_id' => 6],
                    0
                ),
            ],
            [
                [], null, 0,
                Cursor::create(
                    $order_by = ['gender' => 'asc', 'user_id' => 'desc'],
                    Pager::resolve()->cursor('unittest')->size(3)->page(11),
                    ['gender' => 2, 'user_id' => 6],
                    0
                ),
                "SELECT * FROM users", $order_by, [], Pager::resolve()->cursor('unittest')->size(3)->page(12),
                Cursor::create(
                    $order_by,
                    Pager::resolve()->cursor('unittest')->size(3)->page(11),
                    ['gender' => 2, 'user_id' => 6],
                    0
                ),
            ],

            //   30, 29, 28, 23, 19, 17, 10, 9, 7, 5, 4, 3, 2, 32, 31, 27, 26, 25, 24, 22, 21, 20, 18, 16, 15, 14, 13, 12, 11, 8, 6, 1 : gender ASC, user_id DESC
            [
                [                                                                                                      12, 11, 8], null, 1,
                Cursor::create(
                    $order_by = ['gender' => 'asc', 'user_id' => 'desc'],
                    Pager::resolve()->cursor('unittest')->size(3)->page(11),
                    ['gender' => 2, 'user_id' => 6],
                    0
                ),
                "SELECT * FROM users", $order_by, [], Pager::resolve()->cursor('unittest')->size(3)->page(10),
                Cursor::create(
                    $order_by,
                    Pager::resolve()->cursor('unittest')->size(3)->page(11),
                    ['gender' => 2, 'user_id' => 6],
                    0
                ),
            ],
            [
                [                                                                                          15, 14, 13], null, 2,
                Cursor::create(
                    $order_by = ['gender' => 'asc', 'user_id' => 'desc'],
                    Pager::resolve()->cursor('unittest')->size(3)->page(10),
                    ['gender' => 2, 'user_id' => 12],
                    1
                ),
                "SELECT * FROM users", $order_by, [], Pager::resolve()->cursor('unittest')->size(3)->page(9),
                Cursor::create(
                    $order_by,
                    Pager::resolve()->cursor('unittest')->size(3)->page(11),
                    ['gender' => 2, 'user_id' => 6],
                    0
                ),
            ],
            [
                [            23, 19, 17], null, 9,
                Cursor::create(
                    $order_by = ['gender' => 'asc', 'user_id' => 'desc'],
                    Pager::resolve()->cursor('unittest')->size(3)->page(3),
                    ['gender' => 1, 'user_id' => 10],
                    8
                ),
                "SELECT * FROM users", $order_by, [], Pager::resolve()->cursor('unittest')->size(3)->page(2),
                Cursor::create(
                    $order_by,
                    Pager::resolve()->cursor('unittest')->size(3)->page(10),
                    ['gender' => 2, 'user_id' => 12],
                    1
                ),
            ],

            //   30, 29, 28, 23, 19, 17, 10, 9, 7, 5, 4, 3, 2, 32, 31, 27, 26, 25, 24, 22, 21, 20, 18, 16, 15, 14, 13, 12, 11, 8, 6, 1 : gender ASC, user_id DESC
            [
                [30, 29, 28], null, 4,
                Cursor::create(
                    $order_by = ['gender' => 'asc', 'user_id' => 'desc'],
                    Pager::resolve()->cursor('unittest')->eachSide(2)->size(3)->page(2),
                    ['gender' => 1, 'user_id' => 23],
                    3
                ),
                "SELECT * FROM users", $order_by, [], Pager::resolve()->cursor('unittest')->eachSide(2)->size(3)->page(1),
                null
            ],
            [
                [            23, 19, 17], null, 3,
                Cursor::create(
                    $order_by = ['gender' => 'asc', 'user_id' => 'desc'],
                    Pager::resolve()->cursor('unittest')->eachSide(2)->size(3)->page(3),
                    ['gender' => 1, 'user_id' => 10],
                    2
                ),
                "SELECT * FROM users", $order_by, [], Pager::resolve()->cursor('unittest')->eachSide(2)->size(3)->page(2),
                Cursor::create(
                    $order_by,
                    Pager::resolve()->cursor('unittest')->eachSide(2)->size(3)->page(2),
                    ['gender' => 1, 'user_id' => 23],
                    3
                ),
            ],
            [
                [                                  5, 4, 3], null, 2,
                Cursor::create(
                    $order_by = ['gender' => 'asc', 'user_id' => 'desc'],
                    Pager::resolve()->cursor('unittest')->eachSide(2)->size(3)->page(5),
                    ['gender' => 1, 'user_id' => 2],
                    1
                ),
                "SELECT * FROM users", $order_by, [], Pager::resolve()->cursor('unittest')->eachSide(2)->size(3)->page(4),
                Cursor::create(
                    $order_by,
                    Pager::resolve()->cursor('unittest')->eachSide(2)->size(3)->page(3),
                    ['gender' => 1, 'user_id' => 10],
                    2
                ),
            ],
            [
                [                                                                                                      12, 11, 8], null, 1,
                Cursor::create(
                    $order_by = ['gender' => 'asc', 'user_id' => 'desc'],
                    Pager::resolve()->cursor('unittest')->eachSide(2)->size(3)->page(11),
                    ['gender' => 2, 'user_id' => 6],
                    0
                ),
                "SELECT * FROM users", $order_by, [], Pager::resolve()->cursor('unittest')->eachSide(2)->size(3)->page(10),
                Cursor::create(
                    $order_by,
                    Pager::resolve()->cursor('unittest')->eachSide(2)->size(3)->page(5),
                    ['gender' => 1, 'user_id' => 2],
                    2
                ),
            ],
            [
                [                                                                                                                 6, 1], null, 0,
                Cursor::create(
                    $order_by = ['gender' => 'asc', 'user_id' => 'desc'],
                    Pager::resolve()->cursor('unittest')->eachSide(2)->size(3)->page(11),
                    ['gender' => 2, 'user_id' => 6],
                    0
                ),
                "SELECT * FROM users", $order_by, [], Pager::resolve()->cursor('unittest')->eachSide(2)->size(3)->page(11),
                Cursor::create(
                    $order_by,
                    Pager::resolve()->cursor('unittest')->eachSide(2)->size(3)->page(11),
                    ['gender' => 2, 'user_id' => 6],
                    0
                ),
            ],
            [
                [], null, 0,
                Cursor::create(
                    $order_by = ['gender' => 'asc', 'user_id' => 'desc'],
                    Pager::resolve()->cursor('unittest')->eachSide(2)->size(3)->page(11),
                    ['gender' => 2, 'user_id' => 6],
                    0
                ),
                "SELECT * FROM users", $order_by, [], Pager::resolve()->cursor('unittest')->eachSide(2)->size(3)->page(12),
                Cursor::create(
                    $order_by,
                    Pager::resolve()->cursor('unittest')->eachSide(2)->size(3)->page(11),
                    ['gender' => 2, 'user_id' => 6],
                    0
                ),
            ],

            //   30, 29, 28, 23, 19, 17, 10, 9, 7, 5, 4, 3, 2, 32, 31, 27, 26, 25, 24, 22, 21, 20, 18, 16, 15, 14, 13, 12, 11, 8, 6, 1 : gender ASC, user_id DESC
            [
                [                                                                                                      12, 11, 8], null, 1,
                Cursor::create(
                    $order_by = ['gender' => 'asc', 'user_id' => 'desc'],
                    Pager::resolve()->cursor('unittest')->eachSide(2)->size(3)->page(11),
                    ['gender' => 2, 'user_id' => 6],
                    0
                ),
                "SELECT * FROM users", $order_by, [], Pager::resolve()->cursor('unittest')->eachSide(2)->size(3)->page(10),
                Cursor::create(
                    $order_by,
                    Pager::resolve()->cursor('unittest')->eachSide(2)->size(3)->page(11),
                    ['gender' => 2, 'user_id' => 6],
                    0
                ),
            ],
            [
                [                                                                                          15, 14, 13], null, 2,
                Cursor::create(
                    $order_by = ['gender' => 'asc', 'user_id' => 'desc'],
                    Pager::resolve()->cursor('unittest')->eachSide(2)->size(3)->page(10),
                    ['gender' => 2, 'user_id' => 12],
                    1
                ),
                "SELECT * FROM users", $order_by, [], Pager::resolve()->cursor('unittest')->eachSide(2)->size(3)->page(9),
                Cursor::create(
                    $order_by,
                    Pager::resolve()->cursor('unittest')->eachSide(2)->size(3)->page(11),
                    ['gender' => 2, 'user_id' => 6],
                    0
                ),
            ],
            [
                [            23, 19, 17], null, 9,
                Cursor::create(
                    $order_by = ['gender' => 'asc', 'user_id' => 'desc'],
                    Pager::resolve()->cursor('unittest')->eachSide(2)->size(3)->page(3),
                    ['gender' => 1, 'user_id' => 10],
                    8
                ),
                "SELECT * FROM users", $order_by, [], Pager::resolve()->cursor('unittest')->eachSide(2)->size(3)->page(2),
                Cursor::create(
                    $order_by,
                    Pager::resolve()->cursor('unittest')->eachSide(2)->size(3)->page(10),
                    ['gender' => 2, 'user_id' => 12],
                    1
                ),
            ],

            //   30, 29, 28, 23, 19, 17, 10, 9, 7, 5, 4, 3, 2, 32, 31, 27, 26, 25, 24, 22, 21, 20, 18, 16, 15, 14, 13, 12, 11, 8, 6, 1 : gender ASC, user_id DESC
            [
                [30, 29, 28], 32, 10,
                Cursor::create(
                    $order_by = ['gender' => 'asc', 'user_id' => 'desc'],
                    Pager::resolve()->cursor('unittest')->needTotal(true)->eachSide(2)->size(3)->page(2),
                    ['gender' => 1, 'user_id' => 23],
                    9
                ),
                "SELECT * FROM users", $order_by, [], Pager::resolve()->cursor('unittest')->needTotal(true)->eachSide(2)->size(3)->page(1),
                null
            ],
            [
                [            23, 19, 17], 32, 9,
                Cursor::create(
                    $order_by = ['gender' => 'asc', 'user_id' => 'desc'],
                    Pager::resolve()->cursor('unittest')->needTotal(true)->eachSide(2)->size(3)->page(3),
                    ['gender' => 1, 'user_id' => 10],
                    8
                ),
                "SELECT * FROM users", $order_by, [], Pager::resolve()->cursor('unittest')->needTotal(true)->eachSide(2)->size(3)->page(2),
                Cursor::create(
                    $order_by,
                    Pager::resolve()->cursor('unittest')->needTotal(true)->eachSide(2)->size(3)->page(2),
                    ['gender' => 1, 'user_id' => 23],
                    9
                ),
            ],
            [
                [                                  5, 4, 3], 32, 7,
                Cursor::create(
                    $order_by = ['gender' => 'asc', 'user_id' => 'desc'],
                    Pager::resolve()->cursor('unittest')->needTotal(true)->eachSide(2)->size(3)->page(5),
                    ['gender' => 1, 'user_id' => 2],
                    6
                ),
                "SELECT * FROM users", $order_by, [], Pager::resolve()->cursor('unittest')->needTotal(true)->eachSide(2)->size(3)->page(4),
                Cursor::create(
                    $order_by,
                    Pager::resolve()->cursor('unittest')->needTotal(true)->eachSide(2)->size(3)->page(3),
                    ['gender' => 1, 'user_id' => 10],
                    8
                ),
            ],
            [
                [                                                                                                      12, 11, 8], 32, 1,
                Cursor::create(
                    $order_by = ['gender' => 'asc', 'user_id' => 'desc'],
                    Pager::resolve()->cursor('unittest')->needTotal(true)->eachSide(2)->size(3)->page(11),
                    ['gender' => 2, 'user_id' => 6],
                    0
                ),
                "SELECT * FROM users", $order_by, [], Pager::resolve()->cursor('unittest')->needTotal(true)->eachSide(2)->size(3)->page(10),
                Cursor::create(
                    $order_by,
                    Pager::resolve()->cursor('unittest')->needTotal(true)->eachSide(2)->size(3)->page(5),
                    ['gender' => 1, 'user_id' => 2],
                    6
                ),
            ],
            [
                [                                                                                                                 6, 1], 32, 0,
                Cursor::create(
                    $order_by = ['gender' => 'asc', 'user_id' => 'desc'],
                    Pager::resolve()->cursor('unittest')->needTotal(true)->eachSide(2)->size(3)->page(11),
                    ['gender' => 2, 'user_id' => 6],
                    0
                ),
                "SELECT * FROM users", $order_by, [], Pager::resolve()->cursor('unittest')->needTotal(true)->eachSide(2)->size(3)->page(11),
                Cursor::create(
                    $order_by,
                    Pager::resolve()->cursor('unittest')->needTotal(true)->eachSide(2)->size(3)->page(11),
                    ['gender' => 2, 'user_id' => 6],
                    0
                ),
            ],
            [
                [], 32, 0,
                Cursor::create(
                    $order_by = ['gender' => 'asc', 'user_id' => 'desc'],
                    Pager::resolve()->cursor('unittest')->needTotal(true)->eachSide(2)->size(3)->page(11),
                    ['gender' => 2, 'user_id' => 6],
                    0
                ),
                "SELECT * FROM users", $order_by, [], Pager::resolve()->cursor('unittest')->needTotal(true)->eachSide(2)->size(3)->page(12),
                Cursor::create(
                    $order_by,
                    Pager::resolve()->cursor('unittest')->needTotal(true)->eachSide(2)->size(3)->page(11),
                    ['gender' => 2, 'user_id' => 6],
                    0
                ),
            ],

            //   30, 29, 28, 23, 19, 17, 10, 9, 7, 5, 4, 3, 2, 32, 31, 27, 26, 25, 24, 22, 21, 20, 18, 16, 15, 14, 13, 12, 11, 8, 6, 1 : gender ASC, user_id DESC
            [
                [                                                                                                      12, 11, 8], 32, 1,
                Cursor::create(
                    $order_by = ['gender' => 'asc', 'user_id' => 'desc'],
                    Pager::resolve()->cursor('unittest')->needTotal(true)->eachSide(2)->size(3)->page(11),
                    ['gender' => 2, 'user_id' => 6],
                    0
                ),
                "SELECT * FROM users", $order_by, [], Pager::resolve()->cursor('unittest')->needTotal(true)->eachSide(2)->size(3)->page(10),
                Cursor::create(
                    $order_by,
                    Pager::resolve()->cursor('unittest')->needTotal(true)->eachSide(2)->size(3)->page(11),
                    ['gender' => 2, 'user_id' => 6],
                    0
                ),
            ],
            [
                [                                                                                          15, 14, 13], 32, 2,
                Cursor::create(
                    $order_by = ['gender' => 'asc', 'user_id' => 'desc'],
                    Pager::resolve()->cursor('unittest')->needTotal(true)->eachSide(2)->size(3)->page(10),
                    ['gender' => 2, 'user_id' => 12],
                    1
                ),
                "SELECT * FROM users", $order_by, [], Pager::resolve()->cursor('unittest')->needTotal(true)->eachSide(2)->size(3)->page(9),
                Cursor::create(
                    $order_by,
                    Pager::resolve()->cursor('unittest')->needTotal(true)->eachSide(2)->size(3)->page(11),
                    ['gender' => 2, 'user_id' => 6],
                    0
                ),
            ],
            [
                [            23, 19, 17], 32, 9,
                Cursor::create(
                    $order_by = ['gender' => 'asc', 'user_id' => 'desc'],
                    Pager::resolve()->cursor('unittest')->needTotal(true)->eachSide(2)->size(3)->page(3),
                    ['gender' => 1, 'user_id' => 10],
                    8
                ),
                "SELECT * FROM users", $order_by, [], Pager::resolve()->cursor('unittest')->needTotal(true)->eachSide(2)->size(3)->page(2),
                Cursor::create(
                    $order_by,
                    Pager::resolve()->cursor('unittest')->needTotal(true)->eachSide(2)->size(3)->page(10),
                    ['gender' => 2, 'user_id' => 12],
                    1
                ),
            ],
        ];
    }

    /**
     * @dataProvider dataPaginates
     */
    public function test_paginate($expect, $expect_total, $expect_next_page_count, $expect_cursor, $sql, $order_by, $params = [], $pager = null, $cursor = null, $class = 'stdClass', $count_optimised_sql = null)
    {
        $this->eachDb(function (Database $db) use ($expect, $expect_total, $expect_next_page_count, $expect_cursor, $sql, $order_by, $params, $pager, $cursor, $class, $count_optimised_sql) {
            Cursor::clear();
            if ($cursor) {
                $cursor->save();
            }
            // $db->debug();
            $paginator = $db->paginate($sql, $order_by, $pager, $params, $class, $count_optimised_sql);
            // $db->debug(false);
            $this->assertInstanceOf(Paginator::class, $paginator);
            $this->assertSame($expect_total, $paginator->total());
            $this->assertSame($expect_next_page_count, $paginator->nextPageCount());
            $rs = Arrays::pluck($paginator->toArray(), 'user_id');
            $this->assertSame($expect, $rs);
            if ($pager->useCursor()) {
                $next_cursor = Cursor::load($pager->cursor());
                if ($expect_cursor) {
                    if (!$expect_cursor->equals($next_cursor)) {
                        $this->fail("Cursor is not equals to expect.\n".var_export($expect_cursor, true)."\n".var_export($next_cursor, true));
                    }
                } else {
                    $this->assertNull($next_cursor);
                }
            }
        });
    }
}
