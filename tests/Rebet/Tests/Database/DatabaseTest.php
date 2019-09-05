<?php
namespace Rebet\Tests\Database\Compiler;

use Exception;
use PHPUnit\Framework\AssertionFailedError;
use Rebet\Config\Config;
use Rebet\Database\Compiler\BuiltinCompiler;
use Rebet\Database\Converter\BuiltinConverter;
use Rebet\Database\Dao;
use Rebet\Database\Database;
use Rebet\Database\Driver\PdoDriver;
use Rebet\Database\Exception\DatabaseException;
use Rebet\Database\Pagination\Pager;
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
        return [
            [[1], 'user_id', "SELECT * FROM users WHERE user_id = 1"],
            [[2, 3, 4, 5, 7, 9, 10, 17, 19, 23, 28, 29, 30], 'user_id', "SELECT * FROM users WHERE gender = 1"],
            [[7, 28, 17, 10, 23, 4, 2, 30, 3, 5, 29, 9, 19], 'user_id', "SELECT * FROM users WHERE gender = 1", ['birthday' => 'desc']],
            [[7, 28, 17, 10, 23, 4, 2, 30, 3, 5, 29, 9, 19], 'user_id', "SELECT * FROM users WHERE gender = :gender", ['birthday' => 'desc'], ['gender' => Gender::MALE()]],

            [[7, 28, 17, 10]          , 'user_id', "SELECT * FROM users WHERE gender = :gender", ['birthday' => 'desc'], ['gender' => Gender::MALE()], Pager::resolve()->size(3)],
            [           [10, 23, 4, 2], 'user_id', "SELECT * FROM users WHERE gender = :gender", ['birthday' => 'desc'], ['gender' => Gender::MALE()], Pager::resolve()->size(3)->page(2)],

            [[4, 5, 6, 7, 8, 9, 10, 11, 12, 13], 'user_id', "SELECT * FROM users", ['user_id' => 'asc'], [], Pager::resolve()->size(3)->page(2)->eachSide(2)],
            [         [7, 8, 9, 10, 11, 12, 13], 'user_id', "SELECT * FROM users", ['user_id' => 'asc'], [], Pager::resolve()->size(3)->page(3)->eachSide(2)],
            [         [7, 8, 9, 10]            , 'user_id', "SELECT * FROM users", ['user_id' => 'asc'], [], Pager::resolve()->size(3)->page(3)->eachSide(2)->needTotal(true)],
            // 7,13,20,28,6,17,10,22,26,23,4,31,24,15,2,30,25,21,11,3,14,1,16,5,29,12,9,8,18,19,32,27 : birthday DESC
            // 30,29,28,23,19,17,10,9,7,5,4,3,2,32,31,27,26,25,24,22,21,20,18,16,15,14,13,12,11,8,6,1 : gender ASC, user_id DESC
        ];
    }

    /**
     * @dataProvider dataQueries
     */
    public function test_query($expect, $col, $sql, $order_by = null, $params = [], $pager = null, $cursor = null)
    {
        $this->eachDb(function (Database $db) use ($expect, $col, $sql, $order_by, $params, $pager, $cursor) {
            $rs = $db->query($sql, $order_by, $params, $pager, $cursor)->allOf($col);
            $this->assertSame($expect, $rs->toArray());
        });
    }

    public function test_paginate()
    {
        // @todo implement
        $this->assertTrue(true);
    }

    // /**
    //  * @dataProvider dataPagings
    //  */
    // public function test_paging(array $target_db_kinds, string $expect_data, array $expect_cursor, array $sql, array $order_by, ?array $params, Pager $pager, ?Cursor $cursor = null)
    // {
    //     foreach (array_keys(Dao::config('dbs')) as $db_kind) {
    //         if (!in_array($db_kind, $target_db_kinds)) {
    //             continue;
    //         }
    //         if (!($db = $this->connect($db_kind))) {
    //             continue;
    //         }

    //         if ($cursor) {
    //             $cursor->save();
    //         }
    //         $paginator = $db->paginate($sql, $order_by, $pager, $params);

    //         // [$compiled_sql, $compiled_params] = $this->compiler->compile($db, $sql, OrderBy::valueOf($order_by), $params, $pager, $cursor);
    //         // $this->assertEquals($expect_sql, $compiled_sql, "on DB '{$db_kind}'");
    //         // $this->assertEquals($expect_params, $compiled_params, "on DB '{$db_kind}'");
    //     }
    // }
}
