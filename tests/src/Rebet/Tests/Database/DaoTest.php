<?php
namespace Rebet\Tests\Database;

use Rebet\Auth\Password;
use Rebet\Database\Dao;
use Rebet\Tests\RebetDatabaseTestCase;
use Rebet\Tools\DateTime\DateTime;

class DaoTest extends RebetDatabaseTestCase
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
            ],
        ][$table_name] ?? [];
    }

    public function test_clear()
    {
        $mysql = Dao::db('mysql');
        $this->assertSame($mysql, Dao::current());
        $this->assertSame(false, $mysql->closed());
        Dao::clear('pgsql');
        $this->assertSame($mysql, Dao::current());
        $this->assertSame(false, $mysql->closed());
        Dao::clear('mysql');
        $this->assertSame(null, Dao::current());
        $this->assertSame(true, $mysql->closed());

        $mysql = Dao::db('mysql');
        $pgsql = Dao::db('pgsql');
        $this->assertSame($pgsql, Dao::current());
        $this->assertSame(false, $mysql->closed());
        $this->assertSame(false, $pgsql->closed());
        Dao::clear();
        $this->assertSame(null, Dao::current());
        $this->assertSame(true, $mysql->closed());
        $this->assertSame(true, $pgsql->closed());
    }

    public function test_db()
    {
        $default = Dao::db();
        $sqlite  = Dao::db('sqlite');
        $this->assertSame($default, $sqlite);

        // @todo Need implement more tests.
    }

    public function test_current()
    {
        $this->assertSame(null, Dao::current());
        $mysql = Dao::db('mysql');
        $this->assertSame($mysql, Dao::current());
        $pgsql = Dao::db('pgsql');
        $this->assertSame($pgsql, Dao::current());
        $mysql = Dao::db('mysql', false);
        $this->assertSame($pgsql, Dao::current());
    }

    public function test___callStatic()
    {
        $this->assertSame('Elody Bode III', Dao::find("SELECT * FROM users WHERE user_id = :user_id", null, ['user_id' => 1])->name);
        $this->assertSame('sqlite', Dao::driverName());
    }
}
