<?php
namespace Rebet\Tests\Database\DataModel;

use App\Model\Article;
use App\Model\Bank;
use App\Model\Fortune;
use App\Model\Group;
use App\Model\GroupUser;
use App\Model\User;
use App\Model\UserWithAnnot;
use App\Enum\Gender;
use Rebet\Database\Database;
use Rebet\Database\Pagination\Pager;
use Rebet\Database\ResultSet;
use Rebet\Tests\RebetDatabaseTestCase;
use Rebet\Tools\DateTime\Date;
use Rebet\Tools\DateTime\DateTime;

class DataModelTest extends RebetDatabaseTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        DateTime::setTestNow('2001-02-03 04:05:06');
    }

    protected function records(string $db_name, string $table_name) : array
    {
        return [
            'users' => [
                ['user_id' => 1 , 'name' => 'Elody Bode III'        , 'gender' => 2, 'birthday' => '1990-01-08', 'email' => 'elody@s1.rebet.local' , 'role' => 'user', 'password' => '$2y$10$iUQ0l38dqjdf.L7OeNpyNuzmYf5qPzXAUwyKhC3G0oqTuUAO5ouci', 'api_token' => 'fe0c1b9ca200d6e01d96f60bab714cbbaffdf89fed5a946ff1b9f024902d2a26'], // password-{user_id}, api-{user_id}
                ['user_id' => 2 , 'name' => 'Alta Hegmann'          , 'gender' => 1, 'birthday' => '2003-02-16', 'email' => 'alta_h@s2.rebet.local', 'role' => 'user', 'password' => '$2y$10$xpouw11HAUb3FAEBXYcwm.kcGmF0.FetTqkQQJFiShY2TiVCwEAQW', 'api_token' => '3d9b9b04a60382dd0f0acb2672b3b87acba7e9a9e44c529ba37baebe1cf9a00c'], // password-{user_id}, api-{user_id}
                ['user_id' => 3 , 'name' => 'Damien Kling'          , 'gender' => 1, 'birthday' => '1992-10-17', 'email' => 'damien@s0.rebet.local', 'role' => 'user', 'password' => '$2y$10$ciYenJCNJh/rKRy9GRNTIO5HQwP0N2t0Hb5db2ESj8Veaty/TjJCe', 'api_token' => 'df38d2697f917ca9460677a98bfbb8baaeabab8e83b9858ea70d6da10b06ad4d'], // password-{user_id}, api-{user_id}
            ],
            'articles' => [
                [/* 'article_id' => 1, */ 'user_id' => 1 , 'subject' => 'article foo     1-1', 'body' => 'body 1-1'],
                [/* 'article_id' => 2, */ 'user_id' => 1 , 'subject' => 'article foo bar 1-2', 'body' => 'body 1-2'],
                [/* 'article_id' => 3, */ 'user_id' => 2 , 'subject' => 'article bar     2-1', 'body' => 'body 2-1'],
                [/* 'article_id' => 4, */ 'user_id' => 1 , 'subject' => 'article baz     1-3', 'body' => 'body 1-3'],
                [/* 'article_id' => 5, */ 'user_id' => 2 , 'subject' => 'article baz qux 2-2', 'body' => 'body 2-2'],
            ],
            'banks' => [
                ['user_id' => 1 , 'name' => 'bank name', 'branch' => 'branch name', 'number' => '1234567', 'holder' => 'Elody Bode III'],
            ],
            'fortunes' => [
                ['gender' => 2, 'birthday' => '1990-01-08', 'result' => 'good'],
                ['gender' => 1, 'birthday' => '2003-02-16', 'result' => 'bad' ],
            ]
        ][$table_name] ?? [];
    }

    public function test_primaryHash()
    {
        $this->assertNotEquals(User::find(1)->primaryHash(), User::find(2)->primaryHash());
        $this->assertNotEquals(Fortune::find(['gender' => Gender::MALE(), 'birthday' => '2003-02-16'])->primaryHash(), Fortune::find(['gender' => Gender::FEMALE(), 'birthday' => '1990-01-08'])->primaryHash());
    }

    public function test_primaryValues()
    {
        $this->assertEquals(['user_id' => 1], User::find(1)->primaryValues());
        $this->assertEquals(['gender' => Gender::MALE(), 'birthday' => Date::valueOf('2003-02-16')], Fortune::find(['gender' => Gender::MALE(), 'birthday' => '2003-02-16'])->primaryValues());
    }

    public function test_foreignHash()
    {
        $this->assertEquals(Article::find(1)->foreignHash(User::class), Article::find(2)->foreignHash(User::class));
        $this->assertNotEquals(Article::find(1)->foreignHash(User::class), Article::find(3)->foreignHash(User::class));
    }

    public function test_foreignValues()
    {
        $this->assertEquals(['user_id' => 1], Article::find(1)->foreignValues(User::class));
        $this->assertEquals(['user_id' => 1], Article::find(2)->foreignValues(User::class));
        $this->assertEquals(['user_id' => 2], Article::find(3)->foreignValues(User::class));
        $this->assertEquals(['gender' => Gender::MALE(), 'birthday' => Date::valueOf('2003-02-16')], User::find(2)->foreignValues(Fortune::class));
    }

    public function test_pluck()
    {
        $this->assertEquals(['user_id' => 1], User::find(1)->pluck('user_id'));
        $this->assertEquals(['user_id' => 1, 'name' => 'Elody Bode III'], User::find(1)->pluck('user_id', 'name'));
    }

    public function test_belongsResultSet()
    {
        $rs   = new ResultSet([]);
        $user = new User();
        $this->assertNull($user->belongsResultSet());
        $this->assertInstanceOf(User::class, $user->belongsResultSet($rs));
        $this->assertSame($rs, $user->belongsResultSet());
    }

    public function test_isSameSourceAs()
    {
        $a = User::find(1);

        $b = User::find(1);
        $this->assertTrue($a->isSameSourceAs($b));
        $this->assertTrue($a == $b);

        $c = User::find(2);
        $this->assertFalse($a->isSameSourceAs($c));
        $this->assertFalse($a == $c);

        $d = User::select()[2];
        $this->assertTrue($a->isSameSourceAs($d));
        $this->assertFalse($a == $d);


        $b->name = 'foo';
        $this->assertTrue($a->isSameSourceAs($b));
        $this->assertFalse($a == $b);
    }

    public function test_isSameAs()
    {
        $a = User::find(1);

        $b = User::find(1);
        $this->assertTrue($a->isSameAs($b));
        $this->assertTrue($a == $b);

        $c = User::find(2);
        $this->assertFalse($a->isSameAs($c));
        $this->assertFalse($a == $c);

        $d = User::select()[2];
        $this->assertTrue($a->isSameAs($d));
        $this->assertFalse($a == $d);


        $b->name = 'foo';
        $this->assertFalse($a->isSameAs($b));
        $this->assertFalse($a == $b);
    }

    public function test_primaryKeys()
    {
        $this->assertSame(['user_id'], User::primaryKeys());
        $this->assertSame(['user_id'], UserWithAnnot::primaryKeys());
        $this->assertSame(['user_id'], Bank::primaryKeys());
        $this->assertSame(['article_id'], Article::primaryKeys());
        $this->assertSame(['group_id'], Group::primaryKeys());
        $this->assertSame(['group_id', 'user_id'], GroupUser::primaryKeys());
    }

    public function test_valueOf()
    {
        $user = User::find(1);
        $this->assertEquals(1, $user->user_id);
        $this->assertEquals('Elody Bode III', $user->name);
        $this->assertEquals(Gender::FEMALE(), $user->gender);
        $this->assertEquals(Date::valueOf('1990-01-08'), $user->birthday);
        $this->assertEquals('elody@s1.rebet.local', $user->email);
        $this->assertEquals('user', $user->role);
    }

    public function test_find()
    {
        $this->eachDb(function (Database $db, string $driver) {
            $user = User::find(1);
            $this->assertEquals(1, $user->user_id);
            $this->assertEquals('Elody Bode III', $user->name);
            $this->assertEquals(Gender::FEMALE(), $user->gender);
            $this->assertEquals(Date::valueOf('1990-01-08'), $user->birthday);
            $this->assertEquals('elody@s1.rebet.local', $user->email);
            $this->assertEquals('user', $user->role);

            $this->clearExecutedSqls();

            $user = User::find(2);
            $this->assertEquals(2, $user->user_id);
            $this->assertEquals('Alta Hegmann', $user->name);
            $this->assertWildcardEach([
                [
                    'sqlsrv' => "/\* Emulated SQL \*/ SELECT TOP 1 \* FROM ?users? WHERE ?user_id? = '2'",
                ][$driver] ?? "/\* Emulated SQL \*/ SELECT \* FROM ?users? WHERE ?user_id? = '2' LIMIT 1",
            ], $this->executedSqls());

            // SQLite not support 'FOR UPDATE'.
            if ($driver === 'sqlite') {
                return;
            }

            $user = User::find(2, true);
            $this->assertEquals(2, $user->user_id);
            $this->assertEquals('Alta Hegmann', $user->name);
            $this->assertWildcardEach([
                [
                    'sqlsrv' => "/\* Emulated SQL \*/ SELECT TOP 1 \* FROM ?users? WITH(UPDLOCK) WHERE ?user_id? = '2'",
                ][$driver] ?? "/\* Emulated SQL \*/ SELECT \* FROM ?users? WHERE ?user_id? = '2' LIMIT 1 FOR UPDATE",
            ], $this->executedSqls());
        });
    }

    public function test_findBy()
    {
        $this->eachDb(function (Database $db, string $driver) {
            $user = User::findBy(['user_id' => 1]);
            $this->assertEquals(1, $user->user_id);
            $this->assertEquals('Elody Bode III', $user->name);
            $this->assertEquals(Gender::FEMALE(), $user->gender);
            $this->assertEquals(Date::valueOf('1990-01-08'), $user->birthday);
            $this->assertEquals('elody@s1.rebet.local', $user->email);
            $this->assertEquals('user', $user->role);

            $this->clearExecutedSqls();

            $user = User::findBy(['name' => 'Alta Hegmann']);
            $this->assertEquals(2, $user->user_id);
            $this->assertEquals('Alta Hegmann', $user->name);
            $this->assertWildcardEach([
                [
                    'sqlsrv' => "/\* Emulated SQL \*/ SELECT TOP 1 \* FROM ?users? WHERE ?name? = 'Alta Hegmann'",
                ][$driver] ?? "/\* Emulated SQL \*/ SELECT \* FROM ?users? WHERE ?name? = 'Alta Hegmann' LIMIT 1",
            ], $this->executedSqls());

            // SQLite not support 'FOR UPDATE'.
            if ($driver === 'sqlite') {
                return;
            }

            $user = User::findBy(['gender' => Gender::MALE(), 'name_contains' => 'Alta'], true);
            $this->assertEquals(2, $user->user_id);
            $this->assertEquals('Alta Hegmann', $user->name);
            $this->assertWildcardEach([
                [
                    'sqlsrv' => "/\* Emulated SQL \*/ SELECT TOP 1 \* FROM ?users? WITH(UPDLOCK) WHERE ?gender? = '1' AND ?name? LIKE '%Alta%' ESCAPE '|'",
                ][$driver] ?? "/\* Emulated SQL \*/ SELECT \* FROM ?users? WHERE ?gender? = '1' AND ?name? LIKE '%Alta%' ESCAPE '|' LIMIT 1 FOR UPDATE",
            ], $this->executedSqls());
        });
    }

    public function test_select()
    {
        $this->eachDb(function (Database $db, string $driver) {
            $this->assertEquals([3, 2, 1], User::select()->pluk('user_id'));
            $this->assertEquals([3, 2], User::select(['gender' => Gender::MALE()])->pluk('user_id'));
            $this->assertEquals([2, 3], User::select(['gender' => Gender::MALE()], ['user_id' => 'asc'])->pluk('user_id'));
            $this->assertEquals([2], User::select(['gender' => Gender::MALE()], ['user_id' => 'asc'], 1)->pluk('user_id'));

            // SQLite not support 'FOR UPDATE'.
            if ($driver === 'sqlite') {
                return;
            }

            $this->clearExecutedSqls();
            $this->assertEquals([2], User::select(['gender' => Gender::MALE()], ['user_id' => 'asc'], 1, true)->pluk('user_id'));
            $this->assertWildcardEach([
                [
                    'sqlsrv' => "/\* Emulated SQL \*/ SELECT TOP 1 \* FROM ?users? WITH(UPDLOCK) WHERE ?gender? = '1' ORDER BY ?user_id? ASC",
                ][$driver] ?? "/\* Emulated SQL \*/ SELECT \* FROM ?users? WHERE ?gender? = '1' ORDER BY ?user_id? ASC LIMIT 1 FOR UPDATE",
            ], $this->executedSqls());
        });
    }

    public function test_paginate()
    {
        $this->eachDb(function (Database $db) {
            $this->assertEquals([3, 2, 1], User::paginate(Pager::resolve())->pluk('user_id'));
            $this->assertEquals([1, 2, 3], User::paginate(Pager::resolve(), [], ['user_id' => 'asc'])->pluk('user_id'));
            $this->assertEquals([3], User::paginate(Pager::resolve()->size(1))->pluk('user_id'));
            $this->assertEquals([3, 2], User::paginate(Pager::resolve()->size(2))->pluk('user_id'));
            $this->assertEquals([2], User::paginate(Pager::resolve()->size(1)->page(2))->pluk('user_id'));
            $this->assertEquals([1], User::paginate(Pager::resolve()->size(2)->page(2))->pluk('user_id'));

            $paginate = Article::paginate(Pager::resolve()->size(2)->page(2)->needTotal(true), ['user_id' => 1]);
            $this->assertEquals([1], $paginate->pluk('article_id'));
            $this->assertEquals(3, $paginate->total());
            $this->assertEquals(1, $paginate->count());
        });
    }

    public function test_belongsTo()
    {
        $this->eachDb(function (Database $db, string $driver) {
            $this->clearExecutedSqls();

            $article = Article::find(1);
            $user    = $article->user();
            $fortune = $user->fortune();
            $this->assertEquals(1, $user->user_id);
            $this->assertEquals('good', $fortune->result);
            $this->assertWildcardEach([
                [
                    'sqlsrv' => "/\* Emulated SQL \*/ SELECT TOP 1 \* FROM ?articles? WHERE ?article_id? = '1'",
                ][$driver] ?? "/\* Emulated SQL \*/ SELECT \* FROM ?articles? WHERE ?article_id? = '1' LIMIT 1",
                [
                    'sqlsrv' => "/\* Emulated SQL \*/ SELECT TOP 1 \* FROM ?users? WHERE ?user_id? = '1'",
                ][$driver] ?? "/\* Emulated SQL \*/ SELECT \* FROM ?users? WHERE ?user_id? = '1' LIMIT 1",
                [
                    'sqlsrv' => "/\* Emulated SQL \*/ SELECT TOP 1 \* FROM ?fortunes? WHERE ?gender? = '2' AND ?birthday? = '1990-01-08'",
                ][$driver] ?? "/\* Emulated SQL \*/ SELECT \* FROM ?fortunes? WHERE ?gender? = '2' AND ?birthday? = '1990-01-08' LIMIT 1",
            ], $this->executedSqls());


            $articles        = Article::select();
            $expect_user_ids = [    2,      1,     2,      1,      1];
            $expect_fortunes = ['bad', 'good', 'bad', 'good', 'good'];
            foreach ($articles as $i => $article) {
                $user    = $article->user();
                $fortune = $user->fortune();
                $this->assertEquals($expect_user_ids[$i], $user->user_id);
                $this->assertEquals($expect_fortunes[$i], $fortune->result);
            }
            $this->assertWildcardEach([
                "/\* Emulated SQL \*/ SELECT \* FROM ?articles? ORDER BY ?article_id? DESC",
                "/\* Emulated SQL \*/ SELECT \* FROM ?users? WHERE ?user_id? IN ('2', '1')",
                "/\* Emulated SQL \*/ SELECT \* FROM ?fortunes? WHERE ((?gender? = '1' AND ?birthday? = '2003-02-16') OR (?gender? = '2' AND ?birthday? = '1990-01-08'))",
            ], $this->executedSqls());


            if ($driver !== 'sqlite') {
                $articles        = Article::select();
                $expect_user_ids = [    2,      1,     2,      1,      1];
                $expect_fortunes = ['bad', 'good', 'bad', 'good', 'good'];
                foreach ($articles as $i => $article) {
                    $user = $article->user(true);
                    $fortune = $user->fortune(true);
                    $this->assertEquals($expect_user_ids[$i], $user->user_id);
                    $this->assertEquals($expect_fortunes[$i], $fortune->result);
                }
                $this->assertWildcardEach([
                    "/\* Emulated SQL \*/ SELECT \* FROM ?articles? ORDER BY ?article_id? DESC",
                    [
                        'sqlsrv' => "/\* Emulated SQL \*/ SELECT \* FROM ?users? WITH(UPDLOCK) WHERE ?user_id? IN ('2', '1')",
                    ][$driver] ?? "/\* Emulated SQL \*/ SELECT \* FROM ?users? WHERE ?user_id? IN ('2', '1') FOR UPDATE",
                    [
                        'sqlsrv' => "/\* Emulated SQL \*/ SELECT \* FROM ?fortunes? WITH(UPDLOCK) WHERE ((?gender? = '1' AND ?birthday? = '2003-02-16') OR (?gender? = '2' AND ?birthday? = '1990-01-08'))",
                    ][$driver] ?? "/\* Emulated SQL \*/ SELECT \* FROM ?fortunes? WHERE ((?gender? = '1' AND ?birthday? = '2003-02-16') OR (?gender? = '2' AND ?birthday? = '1990-01-08')) FOR UPDATE",
                ], $this->executedSqls());
            }


            $articles        = Article::select();
            $expect_user_ids = [    2,      1,     2,      1,      1];
            $expect_fortunes = ['bad', 'good', 'bad', 'good', 'good'];
            foreach ($articles as $i => $article) {
                $user = $article->user(false, false);
                $fortune = $user->fortune();
                $this->assertEquals($expect_user_ids[$i], $user->user_id);
                $this->assertEquals($expect_fortunes[$i], $fortune->result);
            }
            $this->assertWildcardEach([
                "/\* Emulated SQL \*/ SELECT \* FROM ?articles? ORDER BY ?article_id? DESC",
                [
                    'sqlsrv' => "/\* Emulated SQL \*/ SELECT TOP 1 \* FROM ?users? WHERE ?user_id? = '2'",
                ][$driver] ?? "/\* Emulated SQL \*/ SELECT \* FROM ?users? WHERE ?user_id? = '2' LIMIT 1",
                [
                    'sqlsrv' => "/\* Emulated SQL \*/ SELECT TOP 1 \* FROM ?fortunes? WHERE ?gender? = '1' AND ?birthday? = '2003-02-16'",
                ][$driver] ?? "/\* Emulated SQL \*/ SELECT \* FROM ?fortunes? WHERE ?gender? = '1' AND ?birthday? = '2003-02-16' LIMIT 1",
                [
                    'sqlsrv' => "/\* Emulated SQL \*/ SELECT TOP 1 \* FROM ?users? WHERE ?user_id? = '1'",
                ][$driver] ?? "/\* Emulated SQL \*/ SELECT \* FROM ?users? WHERE ?user_id? = '1' LIMIT 1",
                [
                    'sqlsrv' => "/\* Emulated SQL \*/ SELECT TOP 1 \* FROM ?fortunes? WHERE ?gender? = '2' AND ?birthday? = '1990-01-08'",
                ][$driver] ?? "/\* Emulated SQL \*/ SELECT \* FROM ?fortunes? WHERE ?gender? = '2' AND ?birthday? = '1990-01-08' LIMIT 1",
                [
                    'sqlsrv' => "/\* Emulated SQL \*/ SELECT TOP 1 \* FROM ?users? WHERE ?user_id? = '2'",
                ][$driver] ?? "/\* Emulated SQL \*/ SELECT \* FROM ?users? WHERE ?user_id? = '2' LIMIT 1",
                [
                    'sqlsrv' => "/\* Emulated SQL \*/ SELECT TOP 1 \* FROM ?fortunes? WHERE ?gender? = '1' AND ?birthday? = '2003-02-16'",
                ][$driver] ?? "/\* Emulated SQL \*/ SELECT \* FROM ?fortunes? WHERE ?gender? = '1' AND ?birthday? = '2003-02-16' LIMIT 1",
                [
                    'sqlsrv' => "/\* Emulated SQL \*/ SELECT TOP 1 \* FROM ?users? WHERE ?user_id? = '1'",
                ][$driver] ?? "/\* Emulated SQL \*/ SELECT \* FROM ?users? WHERE ?user_id? = '1' LIMIT 1",
                [
                    'sqlsrv' => "/\* Emulated SQL \*/ SELECT TOP 1 \* FROM ?fortunes? WHERE ?gender? = '2' AND ?birthday? = '1990-01-08'",
                ][$driver] ?? "/\* Emulated SQL \*/ SELECT \* FROM ?fortunes? WHERE ?gender? = '2' AND ?birthday? = '1990-01-08' LIMIT 1",
                [
                    'sqlsrv' => "/\* Emulated SQL \*/ SELECT TOP 1 \* FROM ?users? WHERE ?user_id? = '1'",
                ][$driver] ?? "/\* Emulated SQL \*/ SELECT \* FROM ?users? WHERE ?user_id? = '1' LIMIT 1",
                [
                    'sqlsrv' => "/\* Emulated SQL \*/ SELECT TOP 1 \* FROM ?fortunes? WHERE ?gender? = '2' AND ?birthday? = '1990-01-08'",
                ][$driver] ?? "/\* Emulated SQL \*/ SELECT \* FROM ?fortunes? WHERE ?gender? = '2' AND ?birthday? = '1990-01-08' LIMIT 1",
            ], $this->executedSqls());


            $articles        = Article::select();
            $expect_user_ids = [null, null,    3,     2,      1];
            $expect_fortunes = [null, null, null, 'bad', 'good'];
            foreach ($articles as $i => $article) {
                $user    = $article->belongsTo(User::class, ['article_id' => 'user_id']);
                $fortune = $user ? $user->fortune() : null ;
                $this->assertEquals($expect_user_ids[$i], $user ? $user->user_id : null, "{$article->article_id} on {$driver}");
                $this->assertEquals($expect_fortunes[$i], $fortune ? $fortune->result : null);
            }
            $this->assertWildcardEach([
                "/\* Emulated SQL \*/ SELECT \* FROM ?articles? ORDER BY ?article_id? DESC",
                "/\* Emulated SQL \*/ SELECT \* FROM ?users? WHERE ?user_id? IN ('5', '4', '3', '2', '1')",
                "/\* Emulated SQL \*/ SELECT \* FROM ?fortunes? WHERE ((?gender? = '1' AND ?birthday? = '1992-10-17') OR (?gender? = '1' AND ?birthday? = '2003-02-16') OR (?gender? = '2' AND ?birthday? = '1990-01-08'))",
            ], $this->executedSqls());


            $articles        = Article::select();
            $expect_user_ids = [    2,      1,     2,      1,      1];
            $expect_fortunes = ['bad', 'good', 'bad', 'good', 'good'];
            foreach ($articles as $i => $article) {
                $user    = $article->belongsTo(UserWithAnnot::class, []);
                $fortune = $user->fortune();
                $this->assertEquals($expect_user_ids[$i], $user ? $user->user_id : null);
                $this->assertEquals($expect_fortunes[$i], $fortune->result);
            }
            $this->assertWildcardEach([
                "/\* Emulated SQL \*/ SELECT \* FROM ?articles? ORDER BY ?article_id? DESC",
                "/\* Emulated SQL \*/ SELECT \* FROM ?users? WHERE ?user_id? IN ('2', '1')",
                "/\* Emulated SQL \*/ SELECT \* FROM ?fortunes? WHERE ((?gender? = '1' AND ?birthday? = '2003-02-16') OR (?gender? = '2' AND ?birthday? = '1990-01-08'))",
            ], $this->executedSqls());


            $articles        = Article::select();
            $expect_user_ids = [    2,      1,     2,      1,      1];
            $expect_fortunes = ['bad', 'good', 'bad', 'good', 'good'];
            foreach ($articles as $i => $article) {
                $user = $article->user();
                $fortune = $user->fortune();
                $this->assertEquals($expect_user_ids[$i], $user->user_id);
                $this->assertEquals($expect_fortunes[$i], $fortune->result);
            }
            foreach ($articles as $i => $article) {
                $user = $article->user();
                $fortune = $user->fortune();
                $this->assertEquals($expect_user_ids[$i], $user->user_id);
                $this->assertEquals($expect_fortunes[$i], $fortune->result);
            }
            $this->assertWildcardEach([
                "/\* Emulated SQL \*/ SELECT \* FROM ?articles? ORDER BY ?article_id? DESC",
                "/\* Emulated SQL \*/ SELECT \* FROM ?users? WHERE ?user_id? IN ('2', '1')",
                "/\* Emulated SQL \*/ SELECT \* FROM ?fortunes? WHERE ((?gender? = '1' AND ?birthday? = '2003-02-16') OR (?gender? = '2' AND ?birthday? = '1990-01-08'))",
                "/\* Emulated SQL \*/ SELECT \* FROM ?users? WHERE ?user_id? IN ('2', '1')",
                "/\* Emulated SQL \*/ SELECT \* FROM ?fortunes? WHERE ((?gender? = '1' AND ?birthday? = '2003-02-16') OR (?gender? = '2' AND ?birthday? = '1990-01-08'))",
            ], $this->executedSqls());


            $user = User::find(3);
            $fortune = $user->fortune();
            $this->assertNull($fortune);

            $f = new Fortune();
            $f->gender   = $user->gender;
            $f->birthday = $user->birthday;
            $f->result   = 'soso';
            $f->save();

            $fortune = $user->fortune();
            $this->assertNotNull($fortune);
            $this->assertEquals('soso', $fortune->result);
        });
    }

    public function test_hasOne()
    {
        $this->eachDb(function (Database $db, string $driver) {
            $this->clearExecutedSqls();

            $user = User::find(1);
            $bank = $user->bank();
            $this->assertEquals(1, $user->user_id);
            $this->assertEquals('bank name', $bank->name);
            $this->assertWildcardEach([
                [
                    'sqlsrv' => "/\* Emulated SQL \*/ SELECT TOP 1 \* FROM ?users? WHERE ?user_id? = '1'",
                ][$driver] ?? "/\* Emulated SQL \*/ SELECT \* FROM ?users? WHERE ?user_id? = '1' LIMIT 1",
                [
                    'sqlsrv' => "/\* Emulated SQL \*/ SELECT TOP 1 \* FROM ?banks? WHERE ?user_id? = '1'",
                ][$driver] ?? "/\* Emulated SQL \*/ SELECT \* FROM ?banks? WHERE ?user_id? = '1' LIMIT 1",
            ], $this->executedSqls());


            $user = User::find(2);
            $bank = $user->bank();
            $this->assertNull($bank);

            $b = new Bank();
            $b->user_id = $user->user_id;
            $b->name    = 'bank 2';
            $b->branch  = 'branch 2';
            $b->number  = '2222222';
            $b->holder  = $user->name;
            $b->save();

            $bank = $user->bank();
            $this->assertNotNull($bank);
            $this->assertEquals('bank 2', $bank->name);

            $this->clearExecutedSqls();
            $users             = User::select();
            $expect_user_ids   = [   3,        2,           1];
            $expect_bank_names = [null, 'bank 2', 'bank name'];
            foreach ($users as $i => $user) {
                $bank = $user->bank();
                $this->assertEquals($expect_user_ids[$i], $user->user_id);
                $this->assertEquals($expect_bank_names[$i], $bank ? $bank->name : null);
            }
            $this->assertWildcardEach([
                "/\* Emulated SQL \*/ SELECT \* FROM ?users? ORDER BY ?user_id? DESC",
                "/\* Emulated SQL \*/ SELECT \* FROM ?banks? WHERE ?user_id? IN ('3', '2', '1')",
            ], $this->executedSqls());


            $fortunes          = Fortune::select();
            $expect_fortunes   = ['good'     , 'bad'   ];
            $expect_user_ids   = [          1,        2];
            $expect_bank_names = ['bank name', 'bank 2'];
            foreach ($fortunes as $i => $fortune) {
                $user = $fortune->hasOne(User::class);
                $bank = $user->bank();
                $this->assertEquals($expect_fortunes[$i], $fortune->result);
                $this->assertEquals($expect_user_ids[$i], $user->user_id);
                $this->assertEquals($expect_bank_names[$i], $bank ? $bank->name : null);
            }
            $this->assertWildcardEach([
                "/\* Emulated SQL \*/ SELECT \* FROM ?fortunes? ORDER BY ?gender? DESC, ?birthday? DESC",
                "/\* Emulated SQL \*/ SELECT \* FROM ?users? WHERE ((?gender? = '2' AND ?birthday? = '1990-01-08') OR (?gender? = '1' AND ?birthday? = '2003-02-16'))",
                "/\* Emulated SQL \*/ SELECT \* FROM ?banks? WHERE ?user_id? IN ('1', '2')",
            ], $this->executedSqls());


            if ($driver !== 'sqlite') {
                $fortunes          = Fortune::select();
                $expect_fortunes   = ['good'     , 'bad'   ];
                $expect_user_ids   = [          1,        2];
                $expect_bank_names = ['bank name', 'bank 2'];
                foreach ($fortunes as $i => $fortune) {
                    $user = $fortune->users()[0] ?? null;
                    $bank = $user->bank(true, false);
                    $this->assertEquals($expect_fortunes[$i], $fortune->result);
                    $this->assertEquals($expect_user_ids[$i], $user->user_id);
                    $this->assertEquals($expect_bank_names[$i], $bank ? $bank->name : null);
                }
                $this->assertWildcardEach([
                    "/\* Emulated SQL \*/ SELECT \* FROM ?fortunes? ORDER BY ?gender? DESC, ?birthday? DESC",
                    "/\* Emulated SQL \*/ SELECT \* FROM ?users? WHERE ((?gender? = '2' AND ?birthday? = '1990-01-08') OR (?gender? = '1' AND ?birthday? = '2003-02-16')) ORDER BY ?user_id? DESC",
                    [
                        'sqlsrv' => "/\* Emulated SQL \*/ SELECT TOP 1 \* FROM ?banks? WITH(UPDLOCK) WHERE ?user_id? = '1'",
                    ][$driver] ?? "/\* Emulated SQL \*/ SELECT \* FROM ?banks? WHERE ?user_id? = '1' LIMIT 1 FOR UPDATE",
                    [
                        'sqlsrv' => "/\* Emulated SQL \*/ SELECT TOP 1 \* FROM ?banks? WITH(UPDLOCK) WHERE ?user_id? = '2'",
                    ][$driver] ?? "/\* Emulated SQL \*/ SELECT \* FROM ?banks? WHERE ?user_id? = '2' LIMIT 1 FOR UPDATE",
                ], $this->executedSqls());
            }
        });
    }

    public function test_hasMany()
    {
        $this->eachDb(function (Database $db, string $driver) {
            $this->clearExecutedSqls();

            $user               = User::find(1);
            $articles           = $user->articles();
            $expect_article_ids = [4, 2, 1];
            foreach ($articles as $i => $article) {
                $this->assertEquals(1, $article->user_id);
                $this->assertEquals($expect_article_ids[$i], $article->article_id);
            }
            $this->assertWildcardEach([
                [
                    'sqlsrv' => "/\* Emulated SQL \*/ SELECT TOP 1 \* FROM ?users? WHERE ?user_id? = '1'",
                ][$driver] ?? "/\* Emulated SQL \*/ SELECT \* FROM ?users? WHERE ?user_id? = '1' LIMIT 1",
                "/\* Emulated SQL \*/ SELECT \* FROM ?articles? WHERE ?user_id? = '1' ORDER BY ?article_id? DESC",
            ], $this->executedSqls());


            $user               = User::find(1);
            $articles           = $user->articles(['subject_contains' => 'foo']);
            $expect_article_ids = [2, 1];
            foreach ($articles as $i => $article) {
                $this->assertEquals(1, $article->user_id);
                $this->assertEquals($expect_article_ids[$i], $article->article_id);
            }
            $this->assertWildcardEach([
                [
                    'sqlsrv' => "/\* Emulated SQL \*/ SELECT TOP 1 \* FROM ?users? WHERE ?user_id? = '1'",
                ][$driver] ?? "/\* Emulated SQL \*/ SELECT \* FROM ?users? WHERE ?user_id? = '1' LIMIT 1",
                "/\* Emulated SQL \*/ SELECT \* FROM ?articles? WHERE ?subject? LIKE '%foo%' ESCAPE '|' AND ?user_id? = '1' ORDER BY ?article_id? DESC",
            ], $this->executedSqls());


            $user               = User::find(1);
            $articles           = $user->articles([], ['article_id' => 'ASC']);
            $expect_article_ids = [1, 2, 4];
            foreach ($articles as $i => $article) {
                $this->assertEquals(1, $article->user_id);
                $this->assertEquals($expect_article_ids[$i], $article->article_id);
            }
            $this->assertWildcardEach([
                [
                    'sqlsrv' => "/\* Emulated SQL \*/ SELECT TOP 1 \* FROM ?users? WHERE ?user_id? = '1'",
                ][$driver] ?? "/\* Emulated SQL \*/ SELECT \* FROM ?users? WHERE ?user_id? = '1' LIMIT 1",
                "/\* Emulated SQL \*/ SELECT \* FROM ?articles? WHERE ?user_id? = '1' ORDER BY ?article_id? ASC",
            ], $this->executedSqls());


            $user               = User::find(1);
            $articles           = $user->articles([], null, 2);
            $expect_article_ids = [4, 2];
            foreach ($articles as $i => $article) {
                $this->assertEquals(1, $article->user_id);
                $this->assertEquals($expect_article_ids[$i], $article->article_id);
            }
            $this->assertWildcardEach([
                [
                    'sqlsrv' => "/\* Emulated SQL \*/ SELECT TOP 1 \* FROM ?users? WHERE ?user_id? = '1'",
                ][$driver] ?? "/\* Emulated SQL \*/ SELECT \* FROM ?users? WHERE ?user_id? = '1' LIMIT 1",
                [
                    'sqlsrv' => "/\* Emulated SQL \*/ SELECT TOP 2 \* FROM ?articles? WHERE ?user_id? = '1' ORDER BY ?article_id? DESC",
                ][$driver] ?? "/\* Emulated SQL \*/ SELECT \* FROM ?articles? WHERE ?user_id? = '1' ORDER BY ?article_id? DESC LIMIT 2",
            ], $this->executedSqls());


            if ($driver !== 'sqlite') {
                $user               = User::find(1);
                $articles           = $user->articles([], null, null, true);
                $expect_article_ids = [4, 2, 1];
                foreach ($articles as $i => $article) {
                    $this->assertEquals(1, $article->user_id);
                    $this->assertEquals($expect_article_ids[$i], $article->article_id);
                }
                $this->assertWildcardEach([
                    [
                        'sqlsrv' => "/\* Emulated SQL \*/ SELECT TOP 1 \* FROM ?users? WHERE ?user_id? = '1'",
                    ][$driver] ?? "/\* Emulated SQL \*/ SELECT \* FROM ?users? WHERE ?user_id? = '1' LIMIT 1",
                    [
                        'sqlsrv' => "/\* Emulated SQL \*/ SELECT \* FROM ?articles? WITH(UPDLOCK) WHERE ?user_id? = '1' ORDER BY ?article_id? DESC",
                    ][$driver] ?? "/\* Emulated SQL \*/ SELECT \* FROM ?articles? WHERE ?user_id? = '1' ORDER BY ?article_id? DESC FOR UPDATE",
                ], $this->executedSqls());
            }


            $users           = User::select();
            $expect_user_ids = [3, 2, 1];
            foreach ($users as $i => $user) {
                $articles           = $user->articles();
                $expect_article_ids = [
                    [],
                    [5, 3],
                    [4, 2, 1],
                ][$i];
                $this->assertEquals($expect_user_ids[$i], $user->user_id);
                if (empty($expect_article_ids)) {
                    $this->assertEmpty($articles);
                }
                foreach ($articles as $j => $article) {
                    $this->assertEquals($user->user_id, $article->user_id);
                    $this->assertEquals($expect_article_ids[$j], $article->article_id);
                    $owner = $article->user();
                    $this->assertEquals($user->user_id, $owner->user_id);
                }
            }
            $this->assertWildcardEach([
                "/\* Emulated SQL \*/ SELECT \* FROM ?users? ORDER BY ?user_id? DESC",
                "/\* Emulated SQL \*/ SELECT \* FROM ?articles? WHERE ?user_id? IN ('3', '2', '1') ORDER BY ?article_id? DESC",
                "/\* Emulated SQL \*/ SELECT \* FROM ?users? WHERE ?user_id? IN ('2', '1')",
            ], $this->executedSqls());


            $users           = User::select();
            $expect_user_ids = [3, 2, 1];
            foreach ($users as $i => $user) {
                $articles           = $user->articles(['subject_contains' => 'baz']);
                $expect_article_ids = [
                    [],
                    [5],
                    [4],
                ][$i];
                $this->assertEquals($expect_user_ids[$i], $user->user_id);
                if (empty($expect_article_ids)) {
                    $this->assertEmpty($articles);
                }
                foreach ($articles as $j => $article) {
                    $this->assertEquals($user->user_id, $article->user_id);
                    $this->assertEquals($expect_article_ids[$j], $article->article_id);
                }
            }
            $this->assertWildcardEach([
                "/\* Emulated SQL \*/ SELECT \* FROM ?users? ORDER BY ?user_id? DESC",
                "/\* Emulated SQL \*/ SELECT \* FROM ?articles? WHERE ?subject? LIKE '%baz%' ESCAPE '|' AND ?user_id? IN ('3', '2', '1') ORDER BY ?article_id? DESC",
            ], $this->executedSqls());


            $users           = User::select();
            $expect_user_ids = [3, 2, 1];
            foreach ($users as $i => $user) {
                $articles           = $user->articles(['subject_contains' => 'foo'], ['article_id' => 'ASC']);
                $expect_article_ids = [
                    [],
                    [],
                    [1, 2],
                ][$i];
                $this->assertEquals($expect_user_ids[$i], $user->user_id);
                if (empty($expect_article_ids)) {
                    $this->assertEmpty($articles);
                }
                foreach ($articles as $j => $article) {
                    $this->assertEquals($user->user_id, $article->user_id);
                    $this->assertEquals($expect_article_ids[$j], $article->article_id);
                    $owner = $article->user();
                    $this->assertEquals($user->user_id, $owner->user_id);
                }
            }
            $this->assertWildcardEach([
                "/\* Emulated SQL \*/ SELECT \* FROM ?users? ORDER BY ?user_id? DESC",
                "/\* Emulated SQL \*/ SELECT \* FROM ?articles? WHERE ?subject? LIKE '%foo%' ESCAPE '|' AND ?user_id? IN ('3', '2', '1') ORDER BY ?article_id? ASC",
                "/\* Emulated SQL \*/ SELECT \* FROM ?users? WHERE ?user_id? IN ('1')",
            ], $this->executedSqls());


            $users           = User::select();
            $expect_user_ids = [3, 2, 1];
            foreach ($users as $i => $user) {
                $articles           = $user->articles([], null, 1);
                $expect_article_ids = [
                    [],
                    [5],
                    [4],
                ][$i];
                $this->assertEquals($expect_user_ids[$i], $user->user_id);
                if (empty($expect_article_ids)) {
                    $this->assertEmpty($articles);
                }
                foreach ($articles as $j => $article) {
                    $this->assertEquals($user->user_id, $article->user_id);
                    $this->assertEquals($expect_article_ids[$j], $article->article_id);
                    $owner = $article->user();
                    $this->assertEquals($user->user_id, $owner->user_id);
                }
            }
            $this->assertWildcardEach([
                "/\* Emulated SQL \*/ SELECT \* FROM ?users? ORDER BY ?user_id? DESC",
                "/\* Emulated SQL \*/ SELECT \* FROM ?articles? WHERE ?user_id? IN ('3', '2', '1') ORDER BY ?article_id? DESC",
                "/\* Emulated SQL \*/ SELECT \* FROM ?users? WHERE ?user_id? IN ('2', '1')",
            ], $this->executedSqls());


            if ($db->driverName() !== 'sqlite') {
                $users           = User::select();
                $expect_user_ids = [3, 2, 1];
                foreach ($users as $i => $user) {
                    $articles           = $user->articles([], null, null, true);
                    $expect_article_ids = [
                        [],
                        [5, 3],
                        [4, 2, 1],
                    ][$i];
                    $this->assertEquals($expect_user_ids[$i], $user->user_id);
                    if (empty($expect_article_ids)) {
                        $this->assertEmpty($articles);
                    }
                    foreach ($articles as $j => $article) {
                        $this->assertEquals($user->user_id, $article->user_id);
                        $this->assertEquals($expect_article_ids[$j], $article->article_id);
                        $owner = $article->user();
                        $this->assertEquals($user->user_id, $owner->user_id);
                    }
                }
                $this->assertWildcardEach([
                    "/\* Emulated SQL \*/ SELECT \* FROM ?users? ORDER BY ?user_id? DESC",
                    [
                        'sqlsrv' => "/\* Emulated SQL \*/ SELECT \* FROM ?articles? WITH(UPDLOCK) WHERE ?user_id? IN ('3', '2', '1') ORDER BY ?article_id? DESC",
                    ][$driver] ?? "/\* Emulated SQL \*/ SELECT \* FROM ?articles? WHERE ?user_id? IN ('3', '2', '1') ORDER BY ?article_id? DESC FOR UPDATE",
                    "/\* Emulated SQL \*/ SELECT \* FROM ?users? WHERE ?user_id? IN ('2', '1')",
                ], $this->executedSqls());
            }


            $users           = User::select();
            $expect_user_ids = [3, 2, 1];
            foreach ($users as $i => $user) {
                $articles           = $user->articles([], null, null, false, false);
                $expect_article_ids = [
                    [],
                    [5, 3],
                    [4, 2, 1],
                ][$i];
                $this->assertEquals($expect_user_ids[$i], $user->user_id);
                if (empty($expect_article_ids)) {
                    $this->assertEmpty($articles);
                }
                foreach ($articles as $j => $article) {
                    $this->assertEquals($user->user_id, $article->user_id);
                    $this->assertEquals($expect_article_ids[$j], $article->article_id);
                    $owner = $article->user();
                    $this->assertEquals($user->user_id, $owner->user_id);
                }
            }
            $this->assertWildcardEach([
                "/\* Emulated SQL \*/ SELECT \* FROM ?users? ORDER BY ?user_id? DESC",
                "/\* Emulated SQL \*/ SELECT \* FROM ?articles? WHERE ?user_id? = '3' ORDER BY ?article_id? DESC",
                "/\* Emulated SQL \*/ SELECT \* FROM ?articles? WHERE ?user_id? = '2' ORDER BY ?article_id? DESC",
                "/\* Emulated SQL \*/ SELECT \* FROM ?users? WHERE ?user_id? IN ('2')",
                "/\* Emulated SQL \*/ SELECT \* FROM ?articles? WHERE ?user_id? = '1' ORDER BY ?article_id? DESC",
                "/\* Emulated SQL \*/ SELECT \* FROM ?users? WHERE ?user_id? IN ('1')",
            ], $this->executedSqls());


            $fortunes         = Fortune::select();
            $expect_fortuness = ['good', 'bad'];
            foreach ($fortunes as $i => $fortune) {
                $users           = $fortune->users();
                $expect_user_ids = [
                    [1],
                    [2],
                ][$i];
                $this->assertEquals($expect_fortuness[$i], $fortune->result);
                foreach ($users as $j => $user) {
                    $articles           = $user->articles();
                    $expect_article_ids = [
                        [[4, 2, 1]],
                        [[5, 3]],
                    ][$i][$j];
                    $this->assertEquals($expect_user_ids[$j], $user->user_id);
                    if (empty($expect_article_ids)) {
                        $this->assertEmpty($articles);
                    }
                    foreach ($articles as $k => $article) {
                        $this->assertEquals($user->user_id, $article->user_id);
                        $this->assertEquals($expect_article_ids[$k], $article->article_id);
                        $owner = $article->user();
                        $this->assertEquals($user->user_id, $owner->user_id);
                    }
                }
            }
            $this->assertWildcardEach([
                "/\* Emulated SQL \*/ SELECT \* FROM ?fortunes? ORDER BY ?gender? DESC, ?birthday? DESC",
                "/\* Emulated SQL \*/ SELECT \* FROM ?users? WHERE ((?gender? = '2' AND ?birthday? = '1990-01-08') OR (?gender? = '1' AND ?birthday? = '2003-02-16')) ORDER BY ?user_id? DESC",
                "/\* Emulated SQL \*/ SELECT \* FROM ?articles? WHERE ?user_id? IN ('1', '2') ORDER BY ?article_id? DESC",
                "/\* Emulated SQL \*/ SELECT \* FROM ?users? WHERE ?user_id? IN ('1', '2')",
            ], $this->executedSqls());
        });
    }
}
