<?php
namespace Rebet\Tests\Database;

use Exception;
use PHPUnit\Framework\AssertionFailedError;
use Rebet\Auth\Password;
use Rebet\Database\Analysis\BuiltinAnalyzer;
use Rebet\Database\Compiler\BuiltinCompiler;
use Rebet\Database\Converter\BuiltinConverter;
use Rebet\Database\Dao;
use Rebet\Database\Database;
use Rebet\Database\Event\BatchDeleted;
use Rebet\Database\Event\BatchDeleting;
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
use Rebet\Event\Event;
use Rebet\Tests\Mock\Entity\Article;
use Rebet\Tests\Mock\Entity\User;
use Rebet\Tests\Mock\Entity\UserWithAnnot;
use Rebet\Tests\Mock\Enum\Gender;
use Rebet\Tests\RebetDatabaseTestCase;
use Rebet\Tools\Config\Config;
use Rebet\Tools\DateTime\Date;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\Math\Decimal;
use Rebet\Tools\Utility\Arrays;
use Rebet\Tools\Utility\Securities;
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
        return static::BASIC_TABLES[$db_name] ?? [];
    }

    protected function records(string $db_name, string $table_name) : array
    {
        return [
            'users' => [
                ['user_id' => 1 , 'name' => 'Elody Bode III'        , 'gender' => 2, 'birthday' => '1990-01-08', 'email' => 'elody@s1.rebet.local' , 'role' => 'user', 'password' => '$2y$10$iUQ0l38dqjdf.L7OeNpyNuzmYf5qPzXAUwyKhC3G0oqTuUAO5ouci', 'api_token' => 'fe0c1b9ca200d6e01d96f60bab714cbbaffdf89fed5a946ff1b9f024902d2a26'], // password-{user_id}, api-{user_id}
                ['user_id' => 2 , 'name' => 'Alta Hegmann'          , 'gender' => 1, 'birthday' => '2003-02-16', 'email' => 'alta_h@s2.rebet.local', 'role' => 'user', 'password' => '$2y$10$xpouw11HAUb3FAEBXYcwm.kcGmF0.FetTqkQQJFiShY2TiVCwEAQW', 'api_token' => '3d9b9b04a60382dd0f0acb2672b3b87acba7e9a9e44c529ba37baebe1cf9a00c'], // password-{user_id}, api-{user_id}
                ['user_id' => 3 , 'name' => 'Damien Kling'          , 'gender' => 1, 'birthday' => '1992-10-17', 'email' => 'damien@s0.rebet.local', 'role' => 'user', 'password' => '$2y$10$ciYenJCNJh/rKRy9GRNTIO5HQwP0N2t0Hb5db2ESj8Veaty/TjJCe', 'api_token' => 'df38d2697f917ca9460677a98bfbb8baaeabab8e83b9858ea70d6da10b06ad4d'], // password-{user_id}, api-{user_id}
                ['user_id' => 4 , 'name' => 'Odie Kozey'            , 'gender' => 1, 'birthday' => '2008-03-23', 'email' => 'odie.k@s3.rebet.local', 'role' => 'user', 'password' => '$2y$10$pWHeMjHNHLuNn8bP.icDUOpre4cUN3jsU5QBn0ywNtrS6zraJnBOK', 'api_token' => 'dcad2c94aab836c65f8d6ea4e6c7b0ff0af31d1f7e18785981513d517869085a'], // password-{user_id}, api-{user_id}
                ['user_id' => 5 , 'name' => 'Shea Douglas'          , 'gender' => 1, 'birthday' => '1988-04-01', 'email' => 'shea.d@s4.rebet.local', 'role' => 'user', 'password' => '$2y$10$Jp.pzsj0ksry3WJwTzCLaeEEPdt22J70y9ewr9RTPSHWhJ2jFhUS.', 'api_token' => 'e99ad6eb30faa7bbd54b491de5ac209e2f05af8bc8b916cdc0424834cc96288b'], // password-{user_id}, api-{user_id}
                ['user_id' => 6 , 'name' => 'Khalil Hickle'         , 'gender' => 2, 'birthday' => '2013-10-03', 'email' => 'khalil@s0.rebet.local', 'role' => 'user', 'password' => '$2y$10$XgdlquA5FmPwbh30j2oTcO.NhgXjtAFKcOhj2H6kSKjIe4F9JJ5LG', 'api_token' => '0fe8c74777889959cad3c080f5929312ed2c5aea14e5fa50d606f60d063e3d89'], // password-{user_id}, api-{user_id}
                ['user_id' => 7 , 'name' => 'Kali Hilll'            , 'gender' => 1, 'birthday' => '2016-08-01', 'email' => 'kali_h@s8.rebet.local', 'role' => 'user', 'password' => '$2y$10$FJjEVWe3hIFWVy.MYlzfxeEGJYuiuIb8h7kwhM4ImeYmqItmCJomW', 'api_token' => '9b499adf9898f41058ea761fcb503b0d65171ef0154ae07ed51e51a6a28bb732'], // password-{user_id}, api-{user_id}
                ['user_id' => 8 , 'name' => 'Kari Kub'              , 'gender' => 2, 'birthday' => '1984-10-21', 'email' => 'kari-k@s0.rebet.local', 'role' => 'user', 'password' => '$2y$10$E92fQoYqHwm8VwuFLFZTSuCl9sJNBArLmqRV1QIJEjJml5GmNHXhS', 'api_token' => '818554e20e03c6899061395f94176490fce8af5d123d1133027abdf1b7506810'], // password-{user_id}, api-{user_id}
                ['user_id' => 9 , 'name' => 'Rodger Weimann'        , 'gender' => 1, 'birthday' => '1985-03-21', 'email' => 'rodger@s3.rebet.local', 'role' => 'user', 'password' => '$2y$10$2wY95O/0OYcxt0HkWcGTPuIGL6uNfA0YK.t8HNgcT4x6esI5FcYHq', 'api_token' => '6cd6eb512ffd20346a092c6ae7cdfe045567138137639478de68f4057a01c662'], // password-{user_id}, api-{user_id}
                ['user_id' => 10, 'name' => 'Nicholaus O\'Conner'   , 'gender' => 1, 'birthday' => '2012-01-29', 'email' => 'nichol@s1.rebet.local', 'role' => 'user', 'password' => '$2y$10$8jVKO61WtOcSrUL3TeitwukVWrsBVzBUu.tsSsbGTervIaczBW8de', 'api_token' => '0d94b59702957a5264bbcc98ba607f3c1cc9b69cb0b885e439e092911603cdcf'], // password-{user_id}, api-{user_id}
                ['user_id' => 11, 'name' => 'Troy Smitham'          , 'gender' => 2, 'birthday' => '1996-01-21', 'email' => 'troy-s@s1.rebet.local', 'role' => 'user', 'password' => '$2y$10$NVHzyhTRCvXm/C.HJrcEoujv6CDJ0ebsyv8GaQFTRokS.AwDniATi', 'api_token' => 'a5702cf7884915985d7e2f0ff6b2189d6fbc1c3cbb009789a9234409c8197caa'], // password-{user_id}, api-{user_id}
                ['user_id' => 12, 'name' => 'Kraig Grant'           , 'gender' => 2, 'birthday' => '1987-01-06', 'email' => 'kraig@s1.rebet.local' , 'role' => 'user', 'password' => '$2y$10$Xu3RdNmbdSJ2NzZB4Qx6EuSYKy8X3uPowvSXMihxYLDlXVwvRwIJO', 'api_token' => 'c91a7280dd59fe5a7254f0a372d58b9316a6aa8ba107be847e59d3c398ab9ff7'], // password-{user_id}, api-{user_id}
                ['user_id' => 13, 'name' => 'Demarcus Bashirian Jr.', 'gender' => 2, 'birthday' => '2014-12-21', 'email' => 'demarc@s2.rebet.local', 'role' => 'user', 'password' => '$2y$10$YEUk0xFv2MgkWCWc1oRkteSHVGKFN0oC9g5vWzyAYDR.FLOdgbeQu', 'api_token' => '48ed7b40d8911a0869349db79b803c9d49f7f4f691dcdefff777d96a15819fa5'], // password-{user_id}, api-{user_id}
                ['user_id' => 14, 'name' => 'Percy DuBuque'         , 'gender' => 2, 'birthday' => '1990-11-25', 'email' => 'percy@s1.rebet.local' , 'role' => 'user', 'password' => '$2y$10$AVEo9P3baFu40KsTrKd4Je.TYA8uIwLyH/8IQRg2K9Lq2wv39wA1y', 'api_token' => 'f84ba2c46d909e3efcc56e453958217769bb3bec2229d9475b1de375de618cd0'], // password-{user_id}, api-{user_id}
                ['user_id' => 15, 'name' => 'Delpha Weber'          , 'gender' => 2, 'birthday' => '2006-01-29', 'email' => 'delpha@s1.rebet.local', 'role' => 'user', 'password' => '$2y$10$Frghu40V.AUPpQcMcZnzSe65ehUcDJEmNxjnRo.kJ6hP6jskxAxjy', 'api_token' => '694351210bb4e52a55870c1613c43e3d7dfbb48d711a4e9a425a003d412d9a00'], // password-{user_id}, api-{user_id}
                ['user_id' => 16, 'name' => 'Marquise Waters'       , 'gender' => 2, 'birthday' => '1989-08-26', 'email' => 'marqui@s8.rebet.local', 'role' => 'user', 'password' => '$2y$10$yH75mfqKBd7V9trk.v2fXO3EVsEXgNl5sJgOXa8X7zahCTzcaRY1K', 'api_token' => '8c1350328ca2c00f2768511af4c78f69e2869fe5014947b7d563902a80b089e2'], // password-{user_id}, api-{user_id}
                ['user_id' => 17, 'name' => 'Jade Stroman'          , 'gender' => 1, 'birthday' => '2013-08-06', 'email' => 'jade-s@s8.rebet.local', 'role' => 'user', 'password' => '$2y$10$L922Bad/3tJy.frg6xAnzO8NRaksCZxLaI5sAcBX9HvEvtdBCxE5a', 'api_token' => '16fcb04f6a880e990f7362aba97e606f4704f45ca059c230196f9cc38ffd357c'], // password-{user_id}, api-{user_id}
                ['user_id' => 18, 'name' => 'Citlalli Jacobs I'     , 'gender' => 2, 'birthday' => '1983-02-09', 'email' => 'citlal@s2.rebet.local', 'role' => 'user', 'password' => '$2y$10$8gVp/IFmNO9d01n8JZELx.VNcRW6/QT4vo0dJRmblU4qigHLmjQSy', 'api_token' => '1871973f7139abca2ce7c17028351246f932617a0d3c6ce03da35a5358f024b5'], // password-{user_id}, api-{user_id}
                ['user_id' => 19, 'name' => 'Dannie Rutherford'     , 'gender' => 1, 'birthday' => '1982-07-07', 'email' => 'dannie@s7.rebet.local', 'role' => 'user', 'password' => '$2y$10$EmhtQku.OxnPJg5LDZNp3unRUXgKjXCj//6Zr24C57Q.4dxwHoHby', 'api_token' => 'f398c948ca3c1f380ade006887f2ba94abd8625728e963a5a28a3ac794173760'], // password-{user_id}, api-{user_id}
                ['user_id' => 20, 'name' => 'Dayton Herzog'         , 'gender' => 2, 'birthday' => '2014-11-24', 'email' => 'dayton@s1.rebet.local', 'role' => 'user', 'password' => '$2y$10$UCzqYzIMlMSPNlyq5DRStOuEZHLP.LHKYmE9xwQasHdR0XGIeM2N2', 'api_token' => '6459389c936e4d04f472ee055f0a7ed51fe9eb35ca1c1bed642b9915cb57bfa4'], // password-{user_id}, api-{user_id}
                ['user_id' => 21, 'name' => 'Ms. Zoe Hirthe'        , 'gender' => 2, 'birthday' => '1997-02-27', 'email' => 'ms.zo@s2.rebet.local' , 'role' => 'user', 'password' => '$2y$10$TNKnTMo1sO5UYLZGGoMa0.u1Zbk2WTsa80MS62bx3M5MhaJPbvy5y', 'api_token' => '8b3e651cabb6e681d021739a4a5a233e3dba18bba20ab4a11ccd5b935da8c51b'], // password-{user_id}, api-{user_id}
                ['user_id' => 22, 'name' => 'Kaleigh Kassulke'      , 'gender' => 2, 'birthday' => '2011-01-23', 'email' => 'kaleig@s1.rebet.local', 'role' => 'user', 'password' => '$2y$10$AEjrtxBs.d3lO/RuoOK8WuDn44aX29yMJ83fqqZsjfqIrKkICUywy', 'api_token' => '04e6bb083b0abdeab8fb459f061eba53df7cbce820e1105a200509cfbbf6f01a'], // password-{user_id}, api-{user_id}
                ['user_id' => 23, 'name' => 'Deron Macejkovic'      , 'gender' => 1, 'birthday' => '2008-06-18', 'email' => 'deron@s6.rebet.local' , 'role' => 'user', 'password' => '$2y$10$RWVIWK.mutTLu94U25xtY.MFbFb1BTsB9df2fmtltaW2cgNMHnDVS', 'api_token' => '452b414155d0e31bdf194f71fcbc34ccafd6b4637600700254025763ad003074'], // password-{user_id}, api-{user_id}
                ['user_id' => 24, 'name' => 'Mr. Aisha Quigley'     , 'gender' => 2, 'birthday' => '2007-08-29', 'email' => 'mr.ai@s8.rebet.local' , 'role' => 'user', 'password' => '$2y$10$OnJZPFb/k7SZk299ugInnuqPoNyqtn.5h0vt5gtw4Tiwnud7nXjJm', 'api_token' => 'ea1b3878ccb4b27b08e9d0c178e691266691b46716693b486350d3164810439f'], // password-{user_id}, api-{user_id}
                ['user_id' => 25, 'name' => 'Eugenia Friesen II'    , 'gender' => 2, 'birthday' => '1999-12-19', 'email' => 'eugeni@s2.rebet.local', 'role' => 'user', 'password' => '$2y$10$/0fw0.IpdOMyeh/b.c2YNembaKIJBcHoXcp0Y8rVc6IHoWJFVoZDC', 'api_token' => '0a8594dfc13daea1460b0c37eda8d4025356df63033d0bc5656634707dd2214d'], // password-{user_id}, api-{user_id}
                ['user_id' => 26, 'name' => 'Wyman Jaskolski'       , 'gender' => 2, 'birthday' => '2010-07-06', 'email' => 'wyman@s7.rebet.local' , 'role' => 'user', 'password' => '$2y$10$Ao9ssqxDJOmZ2TQPjps35.4ggfktcQsHu.fu8VnWlfMprqLuM/Diy', 'api_token' => 'e20e27deed731eb44d77cad4115cf3916d77199a9498f52f58804520f91d99df'], // password-{user_id}, api-{user_id}
                ['user_id' => 27, 'name' => 'Naomi Batz'            , 'gender' => 2, 'birthday' => '1980-03-06', 'email' => 'naomi@s3.rebet.local' , 'role' => 'user', 'password' => '$2y$10$B4XoWWna/5VFEjojuaKBfea0l9wmFsgq3rrUIAdSgmwnUxvkYg/L2', 'api_token' => '7756374111f4b2e5fe28749a952780bc02c7c21f15b2b160b1532aaeae2fa9af'], // password-{user_id}, api-{user_id}
                ['user_id' => 28, 'name' => 'Miss Bud Koepp'        , 'gender' => 1, 'birthday' => '2014-10-22', 'email' => 'missb@s0.rebet.local' , 'role' => 'user', 'password' => '$2y$10$Sje1S7D8TzWxba1c1Td5HuxgAiGwDRUNS/A30fOABZGGaUtoWvjEi', 'api_token' => '941247c22b8f5edabecd7790af9348710f722cb2af7f92ff4bb1f5691872d277'], // password-{user_id}, api-{user_id}
                ['user_id' => 29, 'name' => 'Ms. Harmon Blick'      , 'gender' => 1, 'birthday' => '1987-03-20', 'email' => 'ms.ha@s3.rebet.local' , 'role' => 'user', 'password' => '$2y$10$fKUXisEZUlFFDresjfFpQeSusS5kkgfT82.6mXbRhtnK3hKU7BKTS', 'api_token' => '34c3c930ba2873cf52eebed80548e86240bc26652ec9e9691a2083a019cb0ddf'], // password-{user_id}, api-{user_id}
                ['user_id' => 30, 'name' => 'Pinkie Kiehn'          , 'gender' => 1, 'birthday' => '2002-01-06', 'email' => 'pinkie@s1.rebet.local', 'role' => 'user', 'password' => '$2y$10$4.rmfUTMXLiJzf97dbCd.eJvOjHryX/rYadAtl/uaKgQs3u6UJFmy', 'api_token' => '644a3456a000f3dd7b3214a25b1e264282afc71b299e1e221c6001feb85c897d'], // password-{user_id}, api-{user_id}
                ['user_id' => 31, 'name' => 'Harmony Feil'          , 'gender' => 2, 'birthday' => '2007-11-03', 'email' => 'harmon@s1.rebet.local', 'role' => 'user', 'password' => '$2y$10$GU25bSnYsfK8qN57ilqFM.fsy50iQoHHj4md3AnuU0t87ZatkF2dG', 'api_token' => '80fb7fddf941d11fea7e48f5877c3e989757d6dbac9f80feff2daef288ce1c56'], // password-{user_id}, api-{user_id}
                ['user_id' => 32, 'name' => 'River Pagac'           , 'gender' => 2, 'birthday' => '1980-11-20', 'email' => 'river@s1.rebet.local' , 'role' => 'user', 'password' => '$2y$10$AypirtnvQ7sKQP/UMnONIegts.IpS1cWgHzQKn0Jub.9AAMKs5.w.', 'api_token' => 'd439fcbdaa35e3566d57d379d26c2d062bb1ec53854acc264dbd15335b2e2672'], // password-{user_id}, api-{user_id}
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
        $this->assertSame('sqlite', Dao::db('sqlite')->driverName());
        $this->assertSame('mysql', Dao::db('mysql')->driverName());
        $this->assertSame('pgsql', Dao::db('pgsql')->driverName());
    }

    public function test_serverVersion()
    {
        $this->eachDb(function (Database $db) {
            $this->assertMatchesRegularExpression('/[0-9]+\.[0-9]+(\.[0-9]+)?/', $db->serverVersion());
        });
    }

    public function test_clientVersion()
    {
        $this->eachDb(function (Database $db) {
            $this->assertMatchesRegularExpression('/[0-9]+\.[0-9]+(\.[0-9]+)?/', $db->clientVersion());
        });
    }

    public function test_pdo()
    {
        $this->eachDb(function (Database $db) {
            $this->assertInstanceOf(\PDO::class, $db->pdo());
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
                        'dsn'              => 'sqlite::memory:',
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

    public function test_rollbackNotQuiet()
    {
        $this->expectException(\PDOException::class);
        $this->expectExceptionMessage("There is no active transaction");

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

            $this->assertSame(1, $db->execute("INSERT INTO users (user_id, name, gender, birthday, email, password) VALUES (:values)", [
                'values' => ['user_id' => 33, 'name' => 'Insert', 'gender' => Gender::MALE(), 'birthday' => Date::createDateTime('1976-04-23'), 'email' => 'foo@bar.local', 'password' => Password::hash('password-33')]
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
            $user->password = Password::hash("password-99");

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
            $user->user_id  = 999;
            $user->password = Password::hash("password-999");

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

    public function test_updateBy()
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
            $this->assertEquals(0, $db->updateBy(User::class, ['name' => 'foo'], ['user_id' => 9999]));
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
            $this->assertEquals(3, $db->updateBy(User::class, ['name' => 'foo', 'role' => 'admin'], ['user_id_lteq' => 3], [], $now));
            $this->assertTrue($updating_event_called);
            $this->assertTrue($updated_event_called);
            foreach ([1, 2, 3] as $user_id) {
                $user = User::find($user_id);
                $this->assertEquals('foo', $user->name);
                $this->assertEquals('admin', $user->role);
                $this->assertEquals($now, $user->updated_at);
            }
            $user = User::find(4);
            $this->assertEquals('Odie Kozey', $user->name);
        });
    }

    public function test_deleteBy()
    {
        $deleting_event_called = false;
        $deleted_event_called  = false;
        Event::listen(function (BatchDeleting $event) use (&$deleting_event_called) {
            $deleting_event_called = true;
        });
        Event::listen(function (BatchDeleted $event) use (&$deleted_event_called) {
            $deleted_event_called = true;
        });

        $this->eachDb(function (Database $db) use (&$deleting_event_called, &$deleted_event_called) {
            $deleting_event_called = false;
            $deleted_event_called  = false;

            $this->assertFalse($deleting_event_called);
            $this->assertFalse($deleted_event_called);
            $this->assertEquals(0, $db->deleteBy(User::class, ['user_id' => 9999]));
            $this->assertTrue($deleting_event_called);
            $this->assertFalse($deleted_event_called);

            $user = User::find(1);
            $deleting_event_called = false;
            $deleted_event_called  = false;
            $this->assertFalse($deleting_event_called);
            $this->assertFalse($deleted_event_called);
            $this->assertNotNull($user);
            $this->assertEquals(3, $db->deleteBy(User::class, ['user_id_lteq' => 3], []));
            $this->assertTrue($deleting_event_called);
            $this->assertTrue($deleted_event_called);
            foreach ([1, 2, 3] as $user_id) {
                $user = User::find($user_id);
                $this->assertNull($user);
            }
            $user = User::find(4);
            $this->assertNotNull($user);
        });
    }

    public function test_existsBy()
    {
        $this->eachDb(function (Database $db) {
            $this->assertFalse($db->existsBy(User::class, ['user_id' => 9999]));
            $this->assertTrue($db->existsBy(User::class, ['user_id' => 1]));
            $this->assertTrue($db->existsBy(User::class, ['user_id' => 1, 'gender' => Gender::FEMALE()]));
            $this->assertFalse($db->existsBy(User::class, ['user_id' => 1, 'gender' => Gender::MALE()]));
            $this->assertTrue($db->existsBy(User::class, ['user_id_lt' => 9999]));
        });
    }

    public function test_countBy()
    {
        $this->eachDb(function (Database $db) {
            $this->assertEquals(0, $db->countBy(User::class, ['user_id' => 9999]));
            $this->assertEquals(1, $db->countBy(User::class, ['user_id' => 1]));
            $this->assertEquals(1, $db->countBy(User::class, ['user_id' => 1, 'gender' => Gender::FEMALE()]));
            $this->assertEquals(0, $db->countBy(User::class, ['user_id' => 1, 'gender' => Gender::MALE()]));
            $this->assertEquals(2, $db->countBy(User::class, ['user_id_lt' => 3]));
            $this->assertEquals(13, $db->countBy(User::class, ['gender' => Gender::MALE()]));
        });
    }

    public function test_close()
    {
        $this->eachDb(function (Database $db) {
            $this->assertInstanceOf(\PDO::class, $db->pdo());
            $db->close();
            try {
                $db->pdo();
                $this->fail('Never execute');
            } catch (\Exception $e) {
                $this->assertInstanceOf(DatabaseException::class, $e);
                $this->assertSame("Database [{$db->name()}] connection was lost.", $e->getMessage());
            }
            Dao::clear($db->name());
        });
    }
}
