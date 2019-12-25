<?php
namespace Rebet\Tests\Database;

use Exception;
use PHPUnit\Framework\AssertionFailedError;
use Rebet\Common\Arrays;
use Rebet\Common\Decimal;
use Rebet\Config\Config;
use Rebet\Database\Analysis\BuiltinAnalyzer;
use Rebet\Database\Compiler\BuiltinCompiler;
use Rebet\Database\Converter\BuiltinConverter;
use Rebet\Database\Dao;
use Rebet\Database\Database;
use Rebet\Database\Driver\Driver;
use Rebet\Database\Driver\PdoDriver;
use Rebet\Database\Event\BatchUpdated;
use Rebet\Database\Event\BatchUpdating;
use Rebet\Database\Event\Created;
use Rebet\Database\Event\Creating;
use Rebet\Database\Event\Deleted;
use Rebet\Database\Event\Deleting;
use Rebet\Database\Event\Updated;
use Rebet\Database\Event\Updating;
use Rebet\Database\Exception\DatabaseException;
use Rebet\Database\Pagination\Cursor;
use Rebet\Database\Pagination\Pager;
use Rebet\Database\Pagination\Paginator;
use Rebet\Database\PdoParameter;
use Rebet\Database\Ransack\BuiltinRansacker;
use Rebet\DateTime\Date;
use Rebet\DateTime\DateTime;
use Rebet\Event\Event;
use Rebet\Tests\Mock\Entity\Article;
use Rebet\Tests\Mock\Entity\User;
use Rebet\Tests\Mock\Entity\UserWithAnnot;
use Rebet\Tests\Mock\Enum\Gender;
use Rebet\Tests\RebetDatabaseTestCase;
use stdClass;

class DatabaseTest extends RebetDatabaseTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        DateTime::setTestNow('2001-02-03 04:05:06');
    }

    protected function tables(string $db_name) : array
    {
        return static::BASIC_TABLES[$db_name === 'main' ? 'sqlite' : $db_name] ?? [];
    }

    protected function records(string $db_name, string $table_name) : array
    {
        return [
            'users' => [
                ['user_id' => 1 , 'name' => 'Elody Bode III'        , 'gender' => 2, 'birthday' => '1990-01-08', 'email' => 'elody@s1.rebet.local' , 'role' => 'user'],
                ['user_id' => 2 , 'name' => 'Alta Hegmann'          , 'gender' => 1, 'birthday' => '2003-02-16', 'email' => 'alta_h@s2.rebet.local', 'role' => 'user'],
                ['user_id' => 3 , 'name' => 'Damien Kling'          , 'gender' => 1, 'birthday' => '1992-10-17', 'email' => 'damien@s0.rebet.local', 'role' => 'user'],
                ['user_id' => 4 , 'name' => 'Odie Kozey'            , 'gender' => 1, 'birthday' => '2008-03-23', 'email' => 'odie.k@s3.rebet.local', 'role' => 'user'],
                ['user_id' => 5 , 'name' => 'Shea Douglas'          , 'gender' => 1, 'birthday' => '1988-04-01', 'email' => 'shea.d@s4.rebet.local', 'role' => 'user'],
                ['user_id' => 6 , 'name' => 'Khalil Hickle'         , 'gender' => 2, 'birthday' => '2013-10-03', 'email' => 'khalil@s0.rebet.local', 'role' => 'user'],
                ['user_id' => 7 , 'name' => 'Kali Hilll'            , 'gender' => 1, 'birthday' => '2016-08-01', 'email' => 'kali_h@s8.rebet.local', 'role' => 'user'],
                ['user_id' => 8 , 'name' => 'Kari Kub'              , 'gender' => 2, 'birthday' => '1984-10-21', 'email' => 'kari-k@s0.rebet.local', 'role' => 'user'],
                ['user_id' => 9 , 'name' => 'Rodger Weimann'        , 'gender' => 1, 'birthday' => '1985-03-21', 'email' => 'rodger@s3.rebet.local', 'role' => 'user'],
                ['user_id' => 10, 'name' => 'Nicholaus O\'Conner'   , 'gender' => 1, 'birthday' => '2012-01-29', 'email' => 'nichol@s1.rebet.local', 'role' => 'user'],
                ['user_id' => 11, 'name' => 'Troy Smitham'          , 'gender' => 2, 'birthday' => '1996-01-21', 'email' => 'troy-s@s1.rebet.local', 'role' => 'user'],
                ['user_id' => 12, 'name' => 'Kraig Grant'           , 'gender' => 2, 'birthday' => '1987-01-06', 'email' => 'kraig@s1.rebet.local' , 'role' => 'user'],
                ['user_id' => 13, 'name' => 'Demarcus Bashirian Jr.', 'gender' => 2, 'birthday' => '2014-12-21', 'email' => 'demarc@s2.rebet.local', 'role' => 'user'],
                ['user_id' => 14, 'name' => 'Percy DuBuque'         , 'gender' => 2, 'birthday' => '1990-11-25', 'email' => 'percy@s1.rebet.local' , 'role' => 'user'],
                ['user_id' => 15, 'name' => 'Delpha Weber'          , 'gender' => 2, 'birthday' => '2006-01-29', 'email' => 'delpha@s1.rebet.local', 'role' => 'user'],
                ['user_id' => 16, 'name' => 'Marquise Waters'       , 'gender' => 2, 'birthday' => '1989-08-26', 'email' => 'marqui@s8.rebet.local', 'role' => 'user'],
                ['user_id' => 17, 'name' => 'Jade Stroman'          , 'gender' => 1, 'birthday' => '2013-08-06', 'email' => 'jade-s@s8.rebet.local', 'role' => 'user'],
                ['user_id' => 18, 'name' => 'Citlalli Jacobs I'     , 'gender' => 2, 'birthday' => '1983-02-09', 'email' => 'citlal@s2.rebet.local', 'role' => 'user'],
                ['user_id' => 19, 'name' => 'Dannie Rutherford'     , 'gender' => 1, 'birthday' => '1982-07-07', 'email' => 'dannie@s7.rebet.local', 'role' => 'user'],
                ['user_id' => 20, 'name' => 'Dayton Herzog'         , 'gender' => 2, 'birthday' => '2014-11-24', 'email' => 'dayton@s1.rebet.local', 'role' => 'user'],
                ['user_id' => 21, 'name' => 'Ms. Zoe Hirthe'        , 'gender' => 2, 'birthday' => '1997-02-27', 'email' => 'ms.zo@s2.rebet.local' , 'role' => 'user'],
                ['user_id' => 22, 'name' => 'Kaleigh Kassulke'      , 'gender' => 2, 'birthday' => '2011-01-23', 'email' => 'kaleig@s1.rebet.local', 'role' => 'user'],
                ['user_id' => 23, 'name' => 'Deron Macejkovic'      , 'gender' => 1, 'birthday' => '2008-06-18', 'email' => 'deron@s6.rebet.local' , 'role' => 'user'],
                ['user_id' => 24, 'name' => 'Mr. Aisha Quigley'     , 'gender' => 2, 'birthday' => '2007-08-29', 'email' => 'mr.ai@s8.rebet.local' , 'role' => 'user'],
                ['user_id' => 25, 'name' => 'Eugenia Friesen II'    , 'gender' => 2, 'birthday' => '1999-12-19', 'email' => 'eugeni@s2.rebet.local', 'role' => 'user'],
                ['user_id' => 26, 'name' => 'Wyman Jaskolski'       , 'gender' => 2, 'birthday' => '2010-07-06', 'email' => 'wyman@s7.rebet.local' , 'role' => 'user'],
                ['user_id' => 27, 'name' => 'Naomi Batz'            , 'gender' => 2, 'birthday' => '1980-03-06', 'email' => 'naomi@s3.rebet.local' , 'role' => 'user'],
                ['user_id' => 28, 'name' => 'Miss Bud Koepp'        , 'gender' => 1, 'birthday' => '2014-10-22', 'email' => 'missb@s0.rebet.local' , 'role' => 'user'],
                ['user_id' => 29, 'name' => 'Ms. Harmon Blick'      , 'gender' => 1, 'birthday' => '1987-03-20', 'email' => 'ms.ha@s3.rebet.local' , 'role' => 'user'],
                ['user_id' => 30, 'name' => 'Pinkie Kiehn'          , 'gender' => 1, 'birthday' => '2002-01-06', 'email' => 'pinkie@s1.rebet.local', 'role' => 'user'],
                ['user_id' => 31, 'name' => 'Harmony Feil'          , 'gender' => 2, 'birthday' => '2007-11-03', 'email' => 'harmon@s1.rebet.local', 'role' => 'user'],
                ['user_id' => 32, 'name' => 'River Pagac'           , 'gender' => 2, 'birthday' => '1980-11-20', 'email' => 'river@s1.rebet.local' , 'role' => 'user'],
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
        $this->eachDb(function (Database $db) {
            $this->assertRegExp('/[0-9]+\.[0-9]+(\.[0-9]+)?/', $db->serverVersion());
        });
    }

    public function test_clientVersion()
    {
        $this->eachDb(function (Database $db) {
            $this->assertRegExp('/[0-9]+\.[0-9]+(\.[0-9]+)?/', $db->clientVersion());
        });
    }

    public function test_driver()
    {
        $this->eachDb(function (Database $db) {
            $this->assertInstanceOf(PdoDriver::class, $db->driver());
        });
    }

    public function test_compiler()
    {
        $this->eachDb(function (Database $db) {
            $this->assertInstanceOf(BuiltinCompiler::class, $db->compiler());
        });
    }

    public function test_converter()
    {
        $this->eachDb(function (Database $db) {
            $this->assertInstanceOf(BuiltinConverter::class, $db->converter());
        });
    }

    public function test_analyzer()
    {
        $this->eachDb(function (Database $db) {
            $this->assertInstanceOf(BuiltinAnalyzer::class, $db->analyzer("SELECT * FROM users"));
        });
    }

    public function test_ransacker()
    {
        $this->eachDb(function (Database $db) {
            $this->assertInstanceOf(BuiltinRansacker::class, $db->ransacker());
        });
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

            $this->assertSame(1, $db->execute("INSERT INTO users (user_id, name, gender, birthday, email) VALUES (:values)", [
                'values' => ['user_id' => 33, 'name' => 'Insert', 'gender' => Gender::MALE(), 'birthday' => Date::createDateTime('1976-04-23'), 'email' => 'foo@bar.local']
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

            $users = $db->select("SELECT * FROM users WHERE gender = :gender", ['user_id' => 'desc'], ['gender' => Gender::MALE()], null, false, User::class);
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
            '01-normal-01' => [[7, 28, 17], null, 1, null, "SELECT * FROM users WHERE gender = :gender", ['birthday' => 'desc'], ['gender' => Gender::MALE()], Pager::resolve()->size(3)],
            '01-normal-02' => [[10, 23, 4], null, 1, null, "SELECT * FROM users WHERE gender = :gender", ['birthday' => 'desc'], ['gender' => Gender::MALE()], Pager::resolve()->size(3)->page(2)],

            // 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32 : user_id ASC
            '02-with_each_side-01' => [[4, 5, 6], null, 3, null, "SELECT * FROM users", ['user_id' => 'asc'], [], Pager::resolve()->size(3)->page(2)->eachSide(2)],
            '02-with_each_side-02' => [[7, 8, 9], null, 2, null, "SELECT * FROM users", ['user_id' => 'asc'], [], Pager::resolve()->size(3)->page(3)->eachSide(2)],
            '02-with_each_side-03' => [[7, 8, 9],   32, 8, null, "SELECT * FROM users", ['user_id' => 'asc'], [], Pager::resolve()->size(3)->page(3)->eachSide(2)->needTotal(true)],

            '03-change_size-01' => [[16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30], null, 1, null, "SELECT * FROM users", ['user_id' => 'asc'], [], Pager::resolve()->size(15)->page(2)->eachSide(2)],

            //   30, 29, 28, 23, 19, 17, 10, 9, 7, 5, 4, 3, 2, 32, 31, 27, 26, 25, 24, 22, 21, 20, 18, 16, 15, 14, 13, 12, 11, 8, 6, 1 : gender ASC, user_id DESC
            '04-simple_paging-01' => [
                [30, 29, 28], null, 1, null,
                "SELECT * FROM users", $order_by = ['gender' => 'asc', 'user_id' => 'desc'], [], $pager = Pager::resolve()->size(3)->page(-1)
            ],
            '04-simple_paging-02' => [
                [30, 29, 28], null, 1, null,
                "SELECT * FROM users", $order_by = ['gender' => 'asc', 'user_id' => 'desc'], [], $pager = Pager::resolve()->size(3)
            ],
            '04-simple_paging-03' => [
                [            23, 19, 17], null, 1, null,
                "SELECT * FROM users", $order_by = ['gender' => 'asc', 'user_id' => 'desc'], [], $pager = Pager::resolve()->size(3)->page(2)
            ],
            '04-simple_paging-04' => [
                [                        10, 9, 7], null, 1, null,
                "SELECT * FROM users", $order_by = ['gender' => 'asc', 'user_id' => 'desc'], [], $pager = Pager::resolve()->size(3)->page(3)
            ],
            '04-simple_paging-05' => [
                [                                  5, 4, 3], null, 1, null,
                "SELECT * FROM users", $order_by = ['gender' => 'asc', 'user_id' => 'desc'], [], $pager = Pager::resolve()->size(3)->page(4)
            ],
            '04-simple_paging-06' => [
                [                                                                                                      12, 11, 8], null, 1, null,
                "SELECT * FROM users", $order_by = ['gender' => 'asc', 'user_id' => 'desc'], [], $pager = Pager::resolve()->size(3)->page(10)
            ],
            '04-simple_paging-07' => [
                [                                                                                                                 6, 1], null, 0, null,
                "SELECT * FROM users", $order_by = ['gender' => 'asc', 'user_id' => 'desc'], [], $pager = Pager::resolve()->size(3)->page(11)
            ],
            '04-simple_paging-08' => [
                [], null, 0, null,
                "SELECT * FROM users", $order_by = ['gender' => 'asc', 'user_id' => 'desc'], [], $pager = Pager::resolve()->size(3)->page(12)
            ],

            //   30, 29, 28, 23, 19, 17, 10, 9, 7, 5, 4, 3, 2, 32, 31, 27, 26, 25, 24, 22, 21, 20, 18, 16, 15, 14, 13, 12, 11, 8, 6, 1 : gender ASC, user_id DESC
            '05-simple_paging_with_cursor-01' => [
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
            '05-simple_paging_with_cursor-02' => [
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
            '05-simple_paging_with_cursor-03' => [
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
            '05-simple_paging_with_cursor-04' => [
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
            '05-simple_paging_with_cursor-05' => [
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
            '05-simple_paging_with_cursor-06' => [
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
            '06-simple_paging_with_cursor_backword-01' => [
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
            '06-simple_paging_with_cursor_backword-02' => [
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
            '06-simple_paging_with_cursor_backword-03' => [
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
            '07-wide_paging_with_cursor-01' => [
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
            '07-wide_paging_with_cursor-02' => [
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
            '07-wide_paging_with_cursor-03' => [
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
            '07-wide_paging_with_cursor-04' => [
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
            '07-wide_paging_with_cursor-05' => [
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
            '07-wide_paging_with_cursor-06' => [
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
            '08-wide_paging_with_cursor_backword-01' => [
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
            '08-wide_paging_with_cursor_backword-02' => [
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
            '08-wide_paging_with_cursor_backword-03' => [
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
            '09-full_paging_with_cursor-01' => [
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
            '09-full_paging_with_cursor-02' => [
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
            '09-full_paging_with_cursor-03' => [
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
            '09-full_paging_with_cursor-04' => [
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
            '09-full_paging_with_cursor-05' => [
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
            '09-full_paging_with_cursor-06' => [
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
            '10-full_paging_with_cursor_backword-01' => [
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
            '10-full_paging_with_cursor_backword-02' => [
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
            '10-full_paging_with_cursor_backword-03' => [
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

            //   30, 29, 28, 23, 19, 17, 10, 9, 7, 5, 4, 3, 2, 32, 31, 27, 26, 25, 24, 22, 21, 20, 18, 16, 15, 14, 13, 12, 11, 8, 6, 1 : gender ASC, user_id DESC
            '11-full_paging_with_cursor_and_optimize_count_sql-01' => [
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
                User::class,
                "SELECT COUNT(*) FROM users",
            ],

            //   30, 29, 28, 23, 19, 17, 10, 9, 7, 5, 4, 3, 2, 32, 31, 27, 26, 25, 24, 22, 21, 20, 18, 16, 15, 14, 13, 12, 11, 8, 6, 1 : gender ASC, user_id DESC
            '12-wide_paging_with_alias_cursor-01' => [
                [            23, 19, 17], null, 3,
                Cursor::create(
                    $order_by = ['user_gender' => 'asc', 'user_id' => 'desc'],
                    Pager::resolve()->cursor('unittest')->eachSide(2)->size(3)->page(3),
                    ['user_gender' => 1, 'user_id' => 10],
                    2
                ),
                "SELECT *, gender AS user_gender FROM users", $order_by, [], Pager::resolve()->cursor('unittest')->eachSide(2)->size(3)->page(2),
                Cursor::create(
                    $order_by,
                    Pager::resolve()->cursor('unittest')->eachSide(2)->size(3)->page(2),
                    ['user_gender' => 1, 'user_id' => 23],
                    3
                ),
            ],
            '12-wide_paging_with_alias_cursor-02' => [
                [            23, 19, 17], null, 3,
                Cursor::create(
                    $order_by = ['user_gender' => 'asc', 'user_id' => 'desc'],
                    Pager::resolve()->cursor('unittest')->eachSide(2)->size(3)->page(3),
                    ['user_gender' => 1, 'user_id' => 10],
                    2
                ),
                "SELECT *, COALESCE(gender, 3) AS user_gender FROM users", $order_by, [], Pager::resolve()->cursor('unittest')->eachSide(2)->size(3)->page(2),
                Cursor::create(
                    $order_by,
                    Pager::resolve()->cursor('unittest')->eachSide(2)->size(3)->page(2),
                    ['user_gender' => 1, 'user_id' => 23],
                    3
                ),
            ],
            '12-wide_paging_with_alias_cursor-03' => [
                [            26, 25, 24], null, 3,
                Cursor::create(
                    $order_by = ['gender_label' => 'asc', 'user_id' => 'desc'],
                    Pager::resolve()->cursor('unittest')->eachSide(2)->size(3)->page(3),
                    ['gender_label' => 'Female', 'user_id' => 22],
                    2
                ),
                "SELECT *, CASE gender WHEN 1 THEN 'Male' ELSE 'Female' END AS gender_label FROM users", $order_by, [], Pager::resolve()->cursor('unittest')->eachSide(2)->size(3)->page(2),
                Cursor::create(
                    $order_by,
                    Pager::resolve()->cursor('unittest')->eachSide(2)->size(3)->page(2),
                    ['gender_label' => 'Female', 'user_id' => 26],
                    3
                ),
            ],
            '12-wide_paging_with_alias_cursor-04' => [
                [            23, 19, 17], null, 3,
                Cursor::create(
                    $order_by = ['user_gender' => 'asc', 'user_id' => 'desc'],
                    Pager::resolve()->cursor('unittest')->eachSide(2)->size(3)->page(3),
                    ['user_gender' => 1, 'user_id' => 10],
                    2
                ),
                "SELECT *, (SELECT gender FROM users AS T WHERE U.user_id = T.user_id) AS user_gender FROM users AS U", $order_by, [], Pager::resolve()->cursor('unittest')->eachSide(2)->size(3)->page(2),
                Cursor::create(
                    $order_by,
                    Pager::resolve()->cursor('unittest')->eachSide(2)->size(3)->page(2),
                    ['user_gender' => 1, 'user_id' => 23],
                    3
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
            $paginator = $db->paginate($sql, $order_by, $pager, $params, false, $class, $count_optimised_sql);
            foreach ($paginator as $row) {
                $this->assertInstanceOf($class, $row);
            }
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

    public function test_find()
    {
        $this->eachDb(function (Database $db) {
            $user = $db->find("SELECT * FROM users WHERE user_id = 0");
            $this->assertNull($user);

            $user = $db->find("SELECT * FROM users WHERE user_id = 1");
            $this->assertInstanceOf(stdClass::class, $user);
            $this->assertEquals(1, $user->user_id);
            $this->assertEquals('Elody Bode III', $user->name);

            $user = $db->find("SELECT * FROM users WHERE user_id IN (1, 2)", ['user_id' => 'desc']);
            $this->assertInstanceOf(stdClass::class, $user);
            $this->assertEquals(2, $user->user_id);
            $this->assertEquals('Alta Hegmann', $user->name);

            $user = $db->find("SELECT * FROM users WHERE user_id = :user_id", [], ['user_id' => 2]);
            $this->assertInstanceOf(stdClass::class, $user);
            $this->assertEquals(2, $user->user_id);
            $this->assertEquals('Alta Hegmann', $user->name);

            $user = $db->find("SELECT * FROM users WHERE user_id = :user_id", [], ['user_id' => 3], false, User::class);
            $this->assertInstanceOf(User::class, $user);
            $this->assertEquals(3, $user->user_id);
            $this->assertEquals('Damien Kling', $user->name);

            $user = $db->find("SELECT * FROM users ORDER BY user_id ASC");
            $this->assertInstanceOf(stdClass::class, $user);
            $this->assertEquals(1, $user->user_id);
            $this->assertEquals('Elody Bode III', $user->name);
        });
    }

    public function test_extract()
    {
        $this->eachDb(function (Database $db) {
            $user_ids = $db->extract("user_id", "SELECT * FROM users WHERE user_id = 0");
            $this->assertSame([], $user_ids->toArray());

            $user_ids = $db->extract("user_id", "SELECT * FROM users WHERE user_id IN (2, 4, 6)");
            $this->assertSame([2, 4, 6], $user_ids->toArray());

            $user_ids = $db->extract(0, "SELECT user_id, name FROM users WHERE user_id IN (2, 4, 6)");
            $this->assertSame([2, 4, 6], $user_ids->toArray());

            $user_names = $db->extract("name", "SELECT * FROM users WHERE user_id IN (2, 4, 6)");
            $this->assertSame(['Alta Hegmann', 'Odie Kozey', 'Khalil Hickle'], $user_names->toArray());

            $user_names = $db->extract(1, "SELECT user_id, name FROM users WHERE user_id IN (2, 4, 6)");
            $this->assertSame(['Alta Hegmann', 'Odie Kozey', 'Khalil Hickle'], $user_names->toArray());

            $user_ids = $db->extract("user_id", "SELECT * FROM users WHERE user_id IN (:user_id)", [], ['user_id' => [2, 4, 6]]);
            $this->assertSame([2, 4, 6], $user_ids->toArray());

            $user_ids = $db->extract("user_id", "SELECT * FROM users WHERE user_id IN (:user_id)", ['user_id' => 'desc'], ['user_id' => [2, 4, 6]]);
            $this->assertSame([6, 4, 2], $user_ids->toArray());

            $user_ids = $db->extract("user_id", "SELECT * FROM users WHERE user_id IN (:user_id)", [], ['user_id' => [2, 4, 6]], 'string');
            $this->assertSame(['2', '4', '6'], $user_ids->toArray());

            $user_birthdays = $db->extract("birthday", "SELECT * FROM users WHERE user_id IN (:user_id)", [], ['user_id' => [2, 4, 6]], Date::class);
            $this->assertEquals([new Date('2003-02-16'), new Date('2008-03-23'), new Date('2013-10-03')], $user_birthdays->toArray());
            foreach ($user_birthdays as $user_birthday) {
                $this->assertInstanceOf(Date::class, $user_birthday);
            }

            $user_birthdays = $db->extract("birthday", "SELECT * FROM users WHERE user_id IN (:user_id)", [], ['user_id' => [2, 4, 6]], 'string');
            $this->assertSame(['2003-02-16', '2008-03-23', '2013-10-03'], $user_birthdays->toArray());
        });
    }

    public function test_get()
    {
        $this->eachDb(function (Database $db) {
            $user_id = $db->get("user_id", "SELECT * FROM users WHERE user_id = 0");
            $this->assertNull($user_id);

            $user_id = $db->get("user_id", "SELECT * FROM users WHERE user_id IN (2, 4, 6)");
            $this->assertSame(2, $user_id);

            $user_id = $db->get(0, "SELECT user_id, name FROM users WHERE user_id IN (2, 4, 6)");
            $this->assertSame(2, $user_id);

            $user_name = $db->get("name", "SELECT * FROM users WHERE user_id = 2");
            $this->assertSame('Alta Hegmann', $user_name);

            $user_name = $db->get(1, "SELECT user_id, name FROM users WHERE user_id IN (2, 4, 6)");
            $this->assertSame('Alta Hegmann', $user_name);

            $user_id = $db->get("user_id", "SELECT * FROM users WHERE user_id IN (2, 4, 6)", ['user_id' => 'desc']);
            $this->assertSame(6, $user_id);

            $user_id = $db->get("user_id", "SELECT * FROM users WHERE user_id = :user_id", [], ['user_id' => 2]);
            $this->assertSame(2, $user_id);

            $user_id = $db->get("user_id", "SELECT * FROM users WHERE user_id = :user_id", [], ['user_id' => 2], 'string');
            $this->assertSame('2', $user_id);

            $user_birthday = $db->get("birthday", "SELECT * FROM users WHERE user_id = :user_id", [], ['user_id' => 2], Date::class);
            $this->assertEquals(new Date('2003-02-16'), $user_birthday);
            $this->assertInstanceOf(Date::class, $user_birthday);

            $user_birthday = $db->get("birthday", "SELECT * FROM users WHERE user_id = :user_id", [], ['user_id' => 2], 'string');
            $this->assertSame('2003-02-16', $user_birthday);
        });
    }

    public function test_exists()
    {
        $this->eachDb(function (Database $db) {
            $this->assertFalse($db->exists("SELECT * FROM users WHERE user_id = 0"));
            $this->assertTrue($db->exists("SELECT * FROM users WHERE user_id = 1"));
            $this->assertTrue($db->exists("SELECT * FROM users WHERE user_id = :user_id", ['user_id' => 1]));
            $this->assertTrue($db->exists("SELECT * FROM users WHERE user_id IN (:user_id)", ['user_id' => [1, 2, 3]]));
            $this->assertFalse($db->exists("SELECT * FROM users WHERE user_id IN (:user_id)", ['user_id' => [998, 999]]));
        });
    }

    public function test_count()
    {
        $this->eachDb(function (Database $db) {
            $this->assertSame(0, $db->count("SELECT * FROM users WHERE user_id = 0"));
            $this->assertSame(1, $db->count("SELECT * FROM users WHERE user_id = 1"));
            $this->assertSame(3, $db->count("SELECT * FROM users WHERE user_id IN (:user_id)", ['user_id' => [1, 2, 3]]));
            $this->assertSame(13, $db->count("SELECT * FROM users WHERE gender = 1"));
        });
    }

    public function test_each()
    {
        $this->eachDb(function (Database $db) {
            $db->each(function (User $user) {
                $this->assertSame(0, $user->user_id % 2);
            }, "SELECT * FROM users WHERE user_id % 2 = 0", null, []);

            $db->each(function (User $user) {
                $this->assertSame(Gender::MALE(), $user->gender);
            }, "SELECT * FROM users WHERE gender = :gender", ['user_id' => 'desc'], ['gender' => Gender::MALE()]);
        });
    }

    public function test_filter()
    {
        $this->eachDb(function (Database $db) {
            $this->assertEquals(
                $db->select("SELECT * FROM users WHERE gender = 1", null, [], null, false, User::class),
                $db->filter(function (User $user) { return $user->gender == Gender::MALE(); }, "SELECT * FROM users")
            );
        });
    }

    public function test_map()
    {
        $this->eachDb(function (Database $db) {
            $this->assertEquals(
                $db->select("SELECT * FROM users", ['user_id' => 'asc'], [], null, false, User::class)->all(),
                $db->map(function (User $user) { return $user; }, "SELECT * FROM users", ['user_id' => 'asc'])->all()
            );
        });
    }

    public function test_reduce()
    {
        $this->eachDb(function (Database $db) {
            $this->assertEquals(
                Decimal::of($db->get(0, "SELECT SUM(user_id) FROM users")),
                Decimal::of($db->reduce(function (User $user, $carry) { return $carry + $user->user_id; }, 0, "SELECT * FROM users"))
            );
        });
    }

    public function test_create()
    {
        $creating_event_called = false;
        $created_event_called  = false;
        Event::listen(function (Creating $event) use (&$creating_event_called) {
            $creating_event_called = true;
            switch (get_class($event->new)) {
                case Article::class:
                    $event->new->body .= ' via creating';
                    break;
            }
        });
        Event::listen(function (Created $event) use (&$created_event_called) {
            $created_event_called = true;
            switch (get_class($event->new)) {
                case Article::class:
                    $this->assertStringEndsWith(' via creating', $event->new->body);
                    break;
            }
        });

        $this->eachDb(function (Database $db) use (&$creating_event_called, &$created_event_called) {
            $creating_event_called = false;
            $created_event_called  = false;

            $this->assertNull(Article::find(1));

            $article = new Article();
            $article->user_id = 1;
            $article->subject = 'Test';
            $article->body    = 'This is test';

            $this->assertFalse($creating_event_called);
            $this->assertFalse($created_event_called);
            $this->assertTrue($db->create($article));
            $this->assertTrue($creating_event_called);
            $this->assertTrue($created_event_called);
            $this->assertEquals(1, $article->article_id);
            $this->assertSame('Test', $article->subject);
            $this->assertSame('This is test via creating', $article->body);

            // Reset milliseconds to compare with DB data where milliseconds are not stored.
            $article->created_at = $article->created_at->startsOfSecond();
            $origin = $article->origin();
            $origin->created_at = $origin->created_at->startsOfSecond();
            $this->assertEquals($article, Article::find(1));


            $user = new User();
            $user->user_id  = 99;
            $user->name     = 'Foo';
            $user->gender   = Gender::FEMALE();
            $user->birthday = new Date('20 years ago');
            $user->email    = 'foo@bar.local';

            $creating_event_called = false;
            $created_event_called  = false;

            $this->assertFalse($creating_event_called);
            $this->assertFalse($created_event_called);
            $this->assertTrue($db->create($user));
            $this->assertTrue($creating_event_called);
            $this->assertTrue($created_event_called);
            $this->assertSame('user', $user->role);

            // Reset milliseconds to compare with DB data where milliseconds are not stored.
            $user->created_at = $user->created_at->startsOfSecond();
            $origin = $user->origin();
            $origin->created_at = $origin->created_at->startsOfSecond();
            $this->assertEquals($user, User::find(99));


            $now = DateTime::now()->startsOfSecond();
            $user = new UserWithAnnot();
            $user->user_id = 999;

            $creating_event_called = false;
            $created_event_called  = false;

            $this->assertFalse($creating_event_called);
            $this->assertFalse($created_event_called);
            $this->assertTrue($db->create($user, $now));
            $this->assertTrue($creating_event_called);
            $this->assertTrue($created_event_called);
            $this->assertSame('foo', $user->name);
            $this->assertSame(Gender::FEMALE(), $user->gender);
            $this->assertEquals($now->modify('20 years ago')->toDate(), $user->birthday);
            $this->assertSame('foo@bar.local', $user->email);
            $this->assertSame('user', $user->role);
            $this->assertEquals($now, $user->created_at);
            $this->assertEquals(null, $user->updated_at);

            $this->assertEquals($user, UserWithAnnot::find(999));
        });
    }

    public function test_update()
    {
        $updating_event_called = false;
        $updated_event_called  = false;
        Event::listen(function (Updating $event) use (&$updating_event_called) {
            $updating_event_called = true;
            switch (get_class($event->new)) {
                case Article::class:
                    $event->new->body .= ' via updating';
                    break;
            }
        });
        Event::listen(function (Updated $event) use (&$updated_event_called) {
            $updated_event_called = true;
            switch (get_class($event->new)) {
                case Article::class:
                    $this->assertStringEndsWith(' via updating', $event->new->body);
                    break;
            }
        });

        $this->eachDb(function (Database $db) use (&$updating_event_called, &$updated_event_called) {
            $article = new Article();
            $article->user_id = 1;
            $article->subject = 'Test';
            $article->body    = 'This is test';
            $this->assertTrue($article->create());

            $updating_event_called = false;
            $updated_event_called  = false;

            $article = Article::find(1);
            $this->assertNull($article->updated_at);
            $this->assertSame('This is test', $article->body);
            $this->assertFalse($updating_event_called);
            $this->assertFalse($updated_event_called);

            $now = DateTime::now()->startsOfSecond();
            $article->body = 'foo';
            $this->assertTrue($db->update($article, $now));
            $this->assertEquals($now, $article->updated_at);
            $this->assertSame('foo via updating', $article->body);

            $this->assertEquals($article, Article::find(1));


            $now = DateTime::now()->startsOfSecond();
            $user = User::find(1);
            $this->assertNull($user->updated_at);
            $this->assertEquals(Gender::FEMALE(), $user->gender);

            $user->gender = Gender::MALE();
            $user->name   = 'John Smith';
            $this->assertTrue($db->update($user, $now));
            $this->assertEquals($now, $user->updated_at);

            $this->assertEquals($user, User::find(1));


            $now = DateTime::now()->startsOfSecond();
            $user = UserWithAnnot::find(2);
            $this->assertNull($user->updated_at);
            $this->assertEquals(Gender::MALE(), $user->gender);

            $user->gender = Gender::FEMALE();
            $user->name   = 'Jane Smith';
            $this->assertTrue($db->update($user, $now));
            $this->assertEquals($now, $user->updated_at);

            $this->assertEquals($user, UserWithAnnot::find(2));
        });
    }

    public function test_save()
    {
        $creating_event_called = false;
        $created_event_called  = false;
        Event::listen(function (Creating $event) use (&$creating_event_called) {
            $creating_event_called = true;
            switch (get_class($event->new)) {
                case Article::class:
                    $event->new->body .= ' via creating';
                    break;
            }
        });
        Event::listen(function (Created $event) use (&$created_event_called) {
            $created_event_called = true;
            switch (get_class($event->new)) {
                case Article::class:
                    $this->assertStringEndsWith(' via creating', $event->new->body);
                    break;
            }
        });
        $updating_event_called = false;
        $updated_event_called  = false;
        Event::listen(function (Updating $event) use (&$updating_event_called) {
            $updating_event_called = true;
            switch (get_class($event->new)) {
                case Article::class:
                    $event->new->body .= ' via updating';
                    break;
            }
        });
        Event::listen(function (Updated $event) use (&$updated_event_called) {
            $updated_event_called = true;
            switch (get_class($event->new)) {
                case Article::class:
                    $this->assertStringEndsWith(' via updating', $event->new->body);
                    break;
            }
        });

        $this->eachDb(function (Database $db) use (&$creating_event_called, &$created_event_called, &$updating_event_called, &$updated_event_called) {
            $creating_event_called = false;
            $created_event_called  = false;
            $updating_event_called = false;
            $updated_event_called  = false;

            $created_at = DateTime::now()->startsOfSecond();

            $article = new Article();
            $article->user_id = 1;
            $article->subject = 'Test';
            $article->body    = 'This is test';

            $this->assertFalse($creating_event_called);
            $this->assertFalse($created_event_called);
            $this->assertTrue($db->save($article, $created_at));
            $this->assertTrue($creating_event_called);
            $this->assertTrue($created_event_called);
            $this->assertEquals(1, $article->article_id);
            $this->assertSame('Test', $article->subject);
            $this->assertSame('This is test via creating', $article->body);

            $this->assertEquals($article, Article::find(1));


            $updated_at = DateTime::now()->startsOfSecond();
            $article->subject = 'Test update';

            $this->assertFalse($updating_event_called);
            $this->assertFalse($updated_event_called);
            $this->assertTrue($db->save($article, $updated_at));
            $this->assertTrue($updating_event_called);
            $this->assertTrue($updated_event_called);
            $this->assertEquals(1, $article->article_id);
            $this->assertSame('Test update', $article->subject);
            $this->assertSame('This is test via creating via updating', $article->body);

            $this->assertEquals($article, Article::find(1));
        });
    }

    public function test_delete()
    {
        $deleting_event_called = false;
        $deleted_event_called  = false;
        Event::listen(function (Deleting $event) use (&$deleting_event_called) {
            $deleting_event_called = true;
        });
        Event::listen(function (Deleted $event) use (&$deleted_event_called) {
            $deleted_event_called = true;
        });

        $this->eachDb(function (Database $db) use (&$deleting_event_called, &$deleted_event_called) {
            $article = new Article();
            $article->user_id = 1;
            $article->subject = 'Test';
            $article->body    = 'This is test';
            $this->assertTrue($article->create());

            $deleting_event_called = false;
            $deleted_event_called  = false;

            $this->assertNotNull(Article::find(1));
            $this->assertFalse($deleting_event_called);
            $this->assertFalse($deleted_event_called);
            $this->assertTrue($db->delete($article));
            $this->assertTrue($deleting_event_called);
            $this->assertTrue($deleted_event_called);
            $this->assertNull(Article::find(1));

            $this->assertNotNull($user = User::find(1));
            $this->assertTrue($db->delete($user));
            $this->assertNull(User::find(1));

            $this->assertNotNull($user = UserWithAnnot::find(2));
            $this->assertTrue($db->delete($user));
            $this->assertNull(UserWithAnnot::find(2));
        });
    }

    public function test_updates()
    {
        $updating_event_called = false;
        $updated_event_called  = false;
        Event::listen(function (BatchUpdating $event) use (&$updating_event_called) {
            $updating_event_called = true;
        });
        Event::listen(function (BatchUpdated $event) use (&$updated_event_called) {
            $updated_event_called = true;
        });

        $this->eachDb(function (Database $db) use (&$updating_event_called, &$updated_event_called) {
            $updating_event_called = false;
            $updated_event_called  = false;

            $this->assertFalse($updating_event_called);
            $this->assertFalse($updated_event_called);
            $this->assertEquals(0, $db->updates(User::class, ['name' => 'foo'], ['user_id' => 9999]));
            $this->assertTrue($updating_event_called);
            $this->assertFalse($updated_event_called);

            $now  = DateTime::now();
            $user = User::find(1);
            $updating_event_called = false;
            $updated_event_called  = false;
            $this->assertFalse($updating_event_called);
            $this->assertFalse($updated_event_called);
            $this->assertEquals('Elody Bode III', $user->name);
            $this->assertEquals(null, $user->updated_at);
            $this->assertEquals(3, $db->updates(User::class, ['name' => 'foo'], ['user_id_lteq' => 3], [], $now));
            $this->assertTrue($updating_event_called);
            $this->assertTrue($updated_event_called);
            foreach ([1, 2, 3] as $user_id) {
                $user = User::find($user_id);
                $this->assertEquals('foo', $user->name);
                $this->assertEquals($now, $user->updated_at);
            }
            $user = User::find(4);
            $this->assertEquals('Odie Kozey', $user->name);
        });
    }

    public function test_close()
    {
        $this->eachDb(function (Database $db) {
            $this->assertInstanceOf(Driver::class, $db->driver());
            $db->close();
            try {
                $db->driver();
                $this->fail('Never execute');
            } catch (\Exception $e) {
                $this->assertInstanceOf(DatabaseException::class, $e);
                $this->assertSame("Database [{$db->name()}] connection was lost.", $e->getMessage());
            }
            Dao::clear($db->name());
        });
    }
}
