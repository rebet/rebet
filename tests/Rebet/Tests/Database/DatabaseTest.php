<?php
namespace Rebet\Tests\Database\Compiler;

use Rebet\DateTime\DateTime;
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
    //     foreach (['sqlite', 'mysql', 'pgsql'] as $db_kind) {
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
