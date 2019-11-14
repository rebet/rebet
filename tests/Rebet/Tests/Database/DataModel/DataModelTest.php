<?php
namespace Rebet\Tests\Database\DataModel;

use Rebet\Database\ResultSet;
use Rebet\DateTime\DateTime;
use Rebet\Tests\Mock\Entity\Article;
use Rebet\Tests\Mock\Entity\Bank;
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
        return static::BASIC_TABLES[$db_name === 'main' ? 'sqlite' : $db_name] ?? [];
    }

    protected function records(string $db_name, string $table_name) : array
    {
        return [
            'users' => [
                ['user_id' => 1 , 'name' => 'Elody Bode III'        , 'gender' => 2, 'birthday' => '1990-01-08', 'email' => 'elody@s1.rebet.local' , 'role' => 'user'],
                ['user_id' => 2 , 'name' => 'Alta Hegmann'          , 'gender' => 1, 'birthday' => '2003-02-16', 'email' => 'alta_h@s2.rebet.local', 'role' => 'user'],
                ['user_id' => 3 , 'name' => 'Damien Kling'          , 'gender' => 1, 'birthday' => '1992-10-17', 'email' => 'damien@s0.rebet.local', 'role' => 'user'],
            ],
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
}
