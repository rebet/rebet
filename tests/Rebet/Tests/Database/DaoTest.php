<?php
namespace Rebet\Tests\Database;

use Rebet\Database\Dao;
use Rebet\Database\Driver\PdoDriver;
use Rebet\Tests\Mock\User;
use Rebet\Tests\RebetDatabaseTestCase;

class DaoTest extends RebetDatabaseTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
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
                        email TEXT NOT NULL,
                        role TEXT NOT NULL,
                        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at TEXT
                    );
EOS

            ]
        ][$db_name] ?? [];
    }

    protected function records(string $db_name, string $table_name) : array
    {
        return [
            'users' => [
                ['user_id' => 1, 'name' => 'John Smith'    , 'gender' => 1, 'birthday' => '1988-01-23', 'email' => 'john@rebet.local', 'role' => 'user'],
                ['user_id' => 2, 'name' => 'Jane Smith'    , 'gender' => 2, 'birthday' => '1999-11-09', 'email' => 'jane@rebet.local', 'role' => 'user'],
                ['user_id' => 3, 'name' => 'Robert Baldwin', 'gender' => 1, 'birthday' => '1991-08-14', 'email' => 'bob@rebet.local' , 'role' => 'user'],
            ],
        ][$table_name] ?? [];
    }

    public function test___construct()
    {
        $this->assertInstanceOf(PdoDriver::class, new PdoDriver('sqlite::memory:'));
    }

    // public function test_sandbox()
    // {
    //     // var_dump(Dao::db()->select('select * from users where user_id = :user$id', ['user$id' => 2]));

    //     // var_dump(Dao::db()->select('select * from users where user_id = :user_id', ['user_id' => 1]));
    //     // $user = new User();
    //     // $user->popurate(['name' => 'Branden Nieves', 'gender' => 1, 'birthday' => '2002-11-30', 'email' => 'nieves@rebet.local' , 'role' => 'user']);
    //     // Dao::db()->insert($user);
    //     // var_dump(Dao::db()->select('select * from users where gender = :gender', ['gender' => 1]));

    //     $user = Dao::db()->find('select * from users where user_id = :user_id', ['user_id' => 1], User::class);
    //     // var_dump($user);
    //     $user->name = 'Updated';
    //     // var_dump($user);
    //     Dao::db()->update($user);
    //     $user = Dao::db()->find('select * from users where user_id = :user_id', ['user_id' => 1], User::class);
    //     // var_dump($user);

    //     // $con  = parent::$pdo;
    //     // $stmt = $con->prepare('select * from users where user_id = :user_id');
    //     // $stmt->execute([':user_id' => 1]);
    //     // foreach ($stmt as $row) {
    //     //     var_dump($row);
    //     // }
    //     // $this->assertSame('', '');
    // }
}
