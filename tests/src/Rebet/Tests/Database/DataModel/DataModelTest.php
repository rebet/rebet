<?php
namespace Rebet\Tests\Database\DataModel;

use Rebet\Config\Config;
use Rebet\Database\Database;
use Rebet\Database\ResultSet;
use Rebet\DateTime\DateTime;
use Rebet\Tests\Mock\Entity\Article;
use Rebet\Tests\Mock\Entity\Bank;
use Rebet\Tests\Mock\Entity\Fortune;
use Rebet\Tests\Mock\Entity\Group;
use Rebet\Tests\Mock\Entity\GroupUser;
use Rebet\Tests\Mock\Entity\User;
use Rebet\Tests\Mock\Entity\UserWithAnnot;
use Rebet\Tests\RebetDatabaseTestCase;

class DataModelTest extends RebetDatabaseTestCase
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
                ['user_id' => 1 , 'name' => 'Elody Bode III'        , 'gender' => 2, 'birthday' => '1990-01-08', 'email' => 'elody@s1.rebet.local' , 'role' => 'user'],
                ['user_id' => 2 , 'name' => 'Alta Hegmann'          , 'gender' => 1, 'birthday' => '2003-02-16', 'email' => 'alta_h@s2.rebet.local', 'role' => 'user'],
                ['user_id' => 3 , 'name' => 'Damien Kling'          , 'gender' => 1, 'birthday' => '1992-10-17', 'email' => 'damien@s0.rebet.local', 'role' => 'user'],
            ],
            'articles' => [
                ['article_id' => 1, 'user_id' => 1 , 'subject' => 'article foo     1-1', 'body' => 'body 1-1'],
                ['article_id' => 2, 'user_id' => 1 , 'subject' => 'article foo bar 1-2', 'body' => 'body 1-2'],
                ['article_id' => 3, 'user_id' => 2 , 'subject' => 'article bar     2-1', 'body' => 'body 2-1'],
                ['article_id' => 4, 'user_id' => 1 , 'subject' => 'article baz     1-3', 'body' => 'body 1-3'],
                ['article_id' => 5, 'user_id' => 2 , 'subject' => 'article baz qux 2-2', 'body' => 'body 2-2'],
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

    public function test_belongsResultSet()
    {
        $rs   = new ResultSet([]);
        $user = new User();
        $this->assertNull($user->belongsResultSet());
        $this->assertInstanceOf(User::class, $user->belongsResultSet($rs));
        $this->assertSame($rs, $user->belongsResultSet());
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

    public function test_belongsTo()
    {
        $sqls = [];
        Config::runtime([
            Database::class => [
                'log_handler' => function (string $name, string $sql, array $params = []) use (&$sqls) {
                    $sqls[] = $sql;
                    // var_dump($sql);
                }
            ],
        ]);
        $this->eachDb(function (Database $db) use (&$sqls) {
            $db->debug(true);

            $sqls    = [];
            $article = Article::find(1);
            $user    = $article->user();
            $fortune = $user->fortune();
            $this->assertEquals(1, $user->user_id);
            $this->assertEquals('good', $fortune->result);
            $this->assertEquals([
                "/* Emulated SQL */ SELECT * FROM articles WHERE article_id = '1' LIMIT 1",
                "/* Emulated SQL */ SELECT * FROM users WHERE user_id = '1' LIMIT 1",
                "/* Emulated SQL */ SELECT * FROM fortunes WHERE gender = '2' AND birthday = '1990-01-08' LIMIT 1",
            ], $sqls);


            $sqls            = [];
            $articles        = Article::select();
            $expect_user_ids = [    2,      1,     2,      1,      1];
            $expect_fortunes = ['bad', 'good', 'bad', 'good', 'good'];
            foreach ($articles as $i => $article) {
                $user    = $article->user();
                $fortune = $user->fortune();
                $this->assertEquals($expect_user_ids[$i], $user->user_id);
                $this->assertEquals($expect_fortunes[$i], $fortune->result);
            }
            $this->assertEquals([
                "/* Emulated SQL */ SELECT * FROM articles ORDER BY article_id DESC",
                "/* Emulated SQL */ SELECT * FROM users WHERE user_id IN ('2', '1')",
                "/* Emulated SQL */ SELECT * FROM fortunes WHERE ((gender = '1' AND birthday = '2003-02-16') OR (gender = '2' AND birthday = '1990-01-08'))",
            ], $sqls);


            if ($db->driverName() !== 'sqlite') {
                $sqls            = [];
                $articles        = Article::select();
                $expect_user_ids = [    2,      1,     2,      1,      1];
                $expect_fortunes = ['bad', 'good', 'bad', 'good', 'good'];
                foreach ($articles as $i => $article) {
                    $user = $article->user(true);
                    $fortune = $user->fortune(true);
                    $this->assertEquals($expect_user_ids[$i], $user->user_id);
                    $this->assertEquals($expect_fortunes[$i], $fortune->result);
                }
                $this->assertEquals([
                    "/* Emulated SQL */ SELECT * FROM articles ORDER BY article_id DESC",
                    "/* Emulated SQL */ SELECT * FROM users WHERE user_id IN ('2', '1') FOR UPDATE",
                    "/* Emulated SQL */ SELECT * FROM fortunes WHERE ((gender = '1' AND birthday = '2003-02-16') OR (gender = '2' AND birthday = '1990-01-08')) FOR UPDATE",
                ], $sqls);
            }


            $sqls            = [];
            $articles        = Article::select();
            $expect_user_ids = [    2,      1,     2,      1,      1];
            $expect_fortunes = ['bad', 'good', 'bad', 'good', 'good'];
            foreach ($articles as $i => $article) {
                $user = $article->user(false, false);
                $fortune = $user->fortune();
                $this->assertEquals($expect_user_ids[$i], $user->user_id);
                $this->assertEquals($expect_fortunes[$i], $fortune->result);
            }
            $this->assertEquals([
                "/* Emulated SQL */ SELECT * FROM articles ORDER BY article_id DESC",
                "/* Emulated SQL */ SELECT * FROM users WHERE user_id = '2' LIMIT 1",
                "/* Emulated SQL */ SELECT * FROM fortunes WHERE gender = '1' AND birthday = '2003-02-16' LIMIT 1",
                "/* Emulated SQL */ SELECT * FROM users WHERE user_id = '1' LIMIT 1",
                "/* Emulated SQL */ SELECT * FROM fortunes WHERE gender = '2' AND birthday = '1990-01-08' LIMIT 1",
                "/* Emulated SQL */ SELECT * FROM users WHERE user_id = '2' LIMIT 1",
                "/* Emulated SQL */ SELECT * FROM fortunes WHERE gender = '1' AND birthday = '2003-02-16' LIMIT 1",
                "/* Emulated SQL */ SELECT * FROM users WHERE user_id = '1' LIMIT 1",
                "/* Emulated SQL */ SELECT * FROM fortunes WHERE gender = '2' AND birthday = '1990-01-08' LIMIT 1",
                "/* Emulated SQL */ SELECT * FROM users WHERE user_id = '1' LIMIT 1",
                "/* Emulated SQL */ SELECT * FROM fortunes WHERE gender = '2' AND birthday = '1990-01-08' LIMIT 1",
            ], $sqls);


            $sqls            = [];
            $articles        = Article::select();
            $expect_user_ids = [null, null,    3,     2,      1];
            $expect_fortunes = [null, null, null, 'bad', 'good'];
            foreach ($articles as $i => $article) {
                $user    = $article->belongsTo(User::class, ['article_id' => 'user_id']);
                $fortune = $user ? $user->fortune() : null ;
                $this->assertEquals($expect_user_ids[$i], $user ? $user->user_id : null);
                $this->assertEquals($expect_fortunes[$i], $fortune ? $fortune->result : null);
            }
            $this->assertEquals([
                "/* Emulated SQL */ SELECT * FROM articles ORDER BY article_id DESC",
                "/* Emulated SQL */ SELECT * FROM users WHERE user_id IN ('5', '4', '3', '2', '1')",
                "/* Emulated SQL */ SELECT * FROM fortunes WHERE ((gender = '1' AND birthday = '1992-10-17') OR (gender = '1' AND birthday = '2003-02-16') OR (gender = '2' AND birthday = '1990-01-08'))",
            ], $sqls);


            $sqls            = [];
            $articles        = Article::select();
            $expect_user_ids = [    2,      1,     2,      1,      1];
            $expect_fortunes = ['bad', 'good', 'bad', 'good', 'good'];
            foreach ($articles as $i => $article) {
                $user    = $article->belongsTo(UserWithAnnot::class, []);
                $fortune = $user->fortune();
                $this->assertEquals($expect_user_ids[$i], $user ? $user->user_id : null);
                $this->assertEquals($expect_fortunes[$i], $fortune->result);
            }
            $this->assertEquals([
                "/* Emulated SQL */ SELECT * FROM articles ORDER BY article_id DESC",
                "/* Emulated SQL */ SELECT * FROM users WHERE user_id IN ('2', '1')",
                "/* Emulated SQL */ SELECT * FROM fortunes WHERE ((gender = '1' AND birthday = '2003-02-16') OR (gender = '2' AND birthday = '1990-01-08'))",
            ], $sqls);


            $sqls            = [];
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
            $this->assertEquals([
                "/* Emulated SQL */ SELECT * FROM articles ORDER BY article_id DESC",
                "/* Emulated SQL */ SELECT * FROM users WHERE user_id IN ('2', '1')",
                "/* Emulated SQL */ SELECT * FROM fortunes WHERE ((gender = '1' AND birthday = '2003-02-16') OR (gender = '2' AND birthday = '1990-01-08'))",
                "/* Emulated SQL */ SELECT * FROM users WHERE user_id IN ('2', '1')",
                "/* Emulated SQL */ SELECT * FROM fortunes WHERE ((gender = '1' AND birthday = '2003-02-16') OR (gender = '2' AND birthday = '1990-01-08'))",
            ], $sqls);


            $sqls = [];
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

            $db->debug(false);
        });
    }

    public function test_hasOne()
    {
        $sqls = [];
        Config::runtime([
            Database::class => [
                'log_handler' => function (string $name, string $sql, array $params = []) use (&$sqls) {
                    $sqls[] = $sql;
                    // var_dump($sql);
                }
            ],
        ]);
        $this->eachDb(function (Database $db) use (&$sqls) {
            $db->debug(true);

            $sqls = [];
            $user = User::find(1);
            $bank = $user->bank();
            $this->assertEquals(1, $user->user_id);
            $this->assertEquals('bank name', $bank->name);
            $this->assertEquals([
                "/* Emulated SQL */ SELECT * FROM users WHERE user_id = '1' LIMIT 1",
                "/* Emulated SQL */ SELECT * FROM banks WHERE user_id = '1' LIMIT 1",
            ], $sqls);


            $sqls = [];
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

            $sqls              = [];
            $users             = User::select();
            $expect_user_ids   = [   3,        2,           1];
            $expect_bank_names = [null, 'bank 2', 'bank name'];
            foreach ($users as $i => $user) {
                $bank = $user->bank();
                $this->assertEquals($expect_user_ids[$i], $user->user_id);
                $this->assertEquals($expect_bank_names[$i], $bank ? $bank->name : null);
            }
            $this->assertEquals([
                "/* Emulated SQL */ SELECT * FROM users ORDER BY user_id DESC",
                "/* Emulated SQL */ SELECT * FROM banks WHERE user_id IN ('3', '2', '1')",
            ], $sqls);


            $sqls              = [];
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
            $this->assertEquals([
                "/* Emulated SQL */ SELECT * FROM fortunes ORDER BY gender DESC, birthday DESC",
                "/* Emulated SQL */ SELECT * FROM users WHERE ((gender = '2' AND birthday = '1990-01-08') OR (gender = '1' AND birthday = '2003-02-16'))",
                "/* Emulated SQL */ SELECT * FROM banks WHERE user_id IN ('1', '2')",
            ], $sqls);


            if ($db->driverName() !== 'sqlite') {
                $sqls              = [];
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
                $this->assertEquals([
                    "/* Emulated SQL */ SELECT * FROM fortunes ORDER BY gender DESC, birthday DESC",
                    "/* Emulated SQL */ SELECT * FROM users WHERE ((gender = '2' AND birthday = '1990-01-08') OR (gender = '1' AND birthday = '2003-02-16')) ORDER BY user_id DESC",
                    "/* Emulated SQL */ SELECT * FROM banks WHERE user_id = '1' LIMIT 1 FOR UPDATE",
                    "/* Emulated SQL */ SELECT * FROM banks WHERE user_id = '2' LIMIT 1 FOR UPDATE",
                ], $sqls);
            }

            $db->debug(false);
        });
    }

    public function test_hasMany()
    {
        $sqls = [];
        Config::runtime([
            Database::class => [
                'log_handler' => function (string $name, string $sql, array $params = []) use (&$sqls) {
                    $sqls[] = $sql;
                    // var_dump($sql);
                }
            ],
        ]);
        $this->eachDb(function (Database $db) use (&$sqls) {
            $db->debug(true);

            $sqls               = [];
            $user               = User::find(1);
            $articles           = $user->articles();
            $expect_article_ids = [4, 2, 1];
            foreach ($articles as $i => $article) {
                $this->assertEquals(1, $article->user_id);
                $this->assertEquals($expect_article_ids[$i], $article->article_id);
            }
            $this->assertEquals([
                "/* Emulated SQL */ SELECT * FROM users WHERE user_id = '1' LIMIT 1",
                "/* Emulated SQL */ SELECT * FROM articles WHERE user_id = '1' ORDER BY article_id DESC",
            ], $sqls);


            $sqls               = [];
            $user               = User::find(1);
            $articles           = $user->articles(['subject_contains' => 'foo']);
            $expect_article_ids = [2, 1];
            foreach ($articles as $i => $article) {
                $this->assertEquals(1, $article->user_id);
                $this->assertEquals($expect_article_ids[$i], $article->article_id);
            }
            $this->assertEquals([
                "/* Emulated SQL */ SELECT * FROM users WHERE user_id = '1' LIMIT 1",
                "/* Emulated SQL */ SELECT * FROM articles WHERE subject LIKE '%foo%' ESCAPE '|' AND user_id = '1' ORDER BY article_id DESC",
            ], $sqls);


            $sqls               = [];
            $user               = User::find(1);
            $articles           = $user->articles([], ['article_id' => 'ASC']);
            $expect_article_ids = [1, 2, 4];
            foreach ($articles as $i => $article) {
                $this->assertEquals(1, $article->user_id);
                $this->assertEquals($expect_article_ids[$i], $article->article_id);
            }
            $this->assertEquals([
                "/* Emulated SQL */ SELECT * FROM users WHERE user_id = '1' LIMIT 1",
                "/* Emulated SQL */ SELECT * FROM articles WHERE user_id = '1' ORDER BY article_id ASC",
            ], $sqls);


            $sqls               = [];
            $user               = User::find(1);
            $articles           = $user->articles([], null, 2);
            $expect_article_ids = [4, 2];
            foreach ($articles as $i => $article) {
                $this->assertEquals(1, $article->user_id);
                $this->assertEquals($expect_article_ids[$i], $article->article_id);
            }
            $this->assertEquals([
                "/* Emulated SQL */ SELECT * FROM users WHERE user_id = '1' LIMIT 1",
                "/* Emulated SQL */ SELECT * FROM articles WHERE user_id = '1' ORDER BY article_id DESC LIMIT 2",
            ], $sqls);


            if ($db->driverName() !== 'sqlite') {
                $sqls               = [];
                $user               = User::find(1);
                $articles           = $user->articles([], null, null, true);
                $expect_article_ids = [4, 2, 1];
                foreach ($articles as $i => $article) {
                    $this->assertEquals(1, $article->user_id);
                    $this->assertEquals($expect_article_ids[$i], $article->article_id);
                }
                $this->assertEquals([
                    "/* Emulated SQL */ SELECT * FROM users WHERE user_id = '1' LIMIT 1",
                    "/* Emulated SQL */ SELECT * FROM articles WHERE user_id = '1' ORDER BY article_id DESC FOR UPDATE",
                ], $sqls);
            }


            $sqls            = [];
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
            $this->assertEquals([
                "/* Emulated SQL */ SELECT * FROM users ORDER BY user_id DESC",
                "/* Emulated SQL */ SELECT * FROM articles WHERE user_id IN ('3', '2', '1') ORDER BY article_id DESC",
                "/* Emulated SQL */ SELECT * FROM users WHERE user_id IN ('2', '1')",
            ], $sqls);


            $sqls            = [];
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
            $this->assertEquals([
                "/* Emulated SQL */ SELECT * FROM users ORDER BY user_id DESC",
                "/* Emulated SQL */ SELECT * FROM articles WHERE subject LIKE '%baz%' ESCAPE '|' AND user_id IN ('3', '2', '1') ORDER BY article_id DESC",
            ], $sqls);


            $sqls            = [];
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
            $this->assertEquals([
                "/* Emulated SQL */ SELECT * FROM users ORDER BY user_id DESC",
                "/* Emulated SQL */ SELECT * FROM articles WHERE subject LIKE '%foo%' ESCAPE '|' AND user_id IN ('3', '2', '1') ORDER BY article_id ASC",
                "/* Emulated SQL */ SELECT * FROM users WHERE user_id IN ('1')",
            ], $sqls);


            $sqls            = [];
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
            $this->assertEquals([
                "/* Emulated SQL */ SELECT * FROM users ORDER BY user_id DESC",
                "/* Emulated SQL */ SELECT * FROM articles WHERE user_id IN ('3', '2', '1') ORDER BY article_id DESC",
                "/* Emulated SQL */ SELECT * FROM users WHERE user_id IN ('2', '1')",
            ], $sqls);


            if ($db->driverName() !== 'sqlite') {
                $sqls            = [];
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
                $this->assertEquals([
                    "/* Emulated SQL */ SELECT * FROM users ORDER BY user_id DESC",
                    "/* Emulated SQL */ SELECT * FROM articles WHERE user_id IN ('3', '2', '1') ORDER BY article_id DESC FOR UPDATE",
                    "/* Emulated SQL */ SELECT * FROM users WHERE user_id IN ('2', '1')",
                ], $sqls);
            }


            $sqls            = [];
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
            $this->assertEquals([
                "/* Emulated SQL */ SELECT * FROM users ORDER BY user_id DESC",
                "/* Emulated SQL */ SELECT * FROM articles WHERE user_id = '3' ORDER BY article_id DESC",
                "/* Emulated SQL */ SELECT * FROM articles WHERE user_id = '2' ORDER BY article_id DESC",
                "/* Emulated SQL */ SELECT * FROM users WHERE user_id IN ('2')",
                "/* Emulated SQL */ SELECT * FROM articles WHERE user_id = '1' ORDER BY article_id DESC",
                "/* Emulated SQL */ SELECT * FROM users WHERE user_id IN ('1')",
            ], $sqls);


            $sqls             = [];
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
            $this->assertEquals([
                "/* Emulated SQL */ SELECT * FROM fortunes ORDER BY gender DESC, birthday DESC",
                "/* Emulated SQL */ SELECT * FROM users WHERE ((gender = '2' AND birthday = '1990-01-08') OR (gender = '1' AND birthday = '2003-02-16')) ORDER BY user_id DESC",
                "/* Emulated SQL */ SELECT * FROM articles WHERE user_id IN ('1', '2') ORDER BY article_id DESC",
                "/* Emulated SQL */ SELECT * FROM users WHERE user_id IN ('1', '2')",
            ], $sqls);

            $db->debug(false);
        });
    }
}