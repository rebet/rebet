<?php
namespace Rebet\Tests\Database;

use PHPUnit\DbUnit\DataSet\ArrayDataSet;
use Rebet\Common\Strings;
use Rebet\Config\Config;
use Rebet\Database\Dao;
use Rebet\Database\Driver\PdoDriver;
use Rebet\Tests\Mock\User;
use Rebet\Tests\RebetDatabaseTestCase;

class DaoTest extends RebetDatabaseTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        Config::application([
            Dao::class => [
                'dbs' => [
                    'main' => [
                        'log_handler' => function ($name, $sql, $param = []) {
                            echo("[DB:{$name}] ".$sql."\n");
                            if (!empty($param)) {
                                echo(Strings::indent("[PARAM]\n".Strings::stringify($param)."\n", '-- '));
                            }
                        }
                    ]
                ]
            ]
        ]);
    }

    public function getSchemaSet() : array
    {
        return [
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
        ];
    }

    protected function getDataSet()
    {
        return new ArrayDataSet([
            'users' => [
                ['user_id' => 1, 'name' => 'John Smith'    , 'gender' => 1, 'birthday' => '1988-01-23', 'email' => 'john@rebet.local', 'role' => 'user'],
                ['user_id' => 2, 'name' => 'Jane Smith'    , 'gender' => 2, 'birthday' => '1999-11-09', 'email' => 'jane@rebet.local', 'role' => 'user'],
                ['user_id' => 3, 'name' => 'Robert Baldwin', 'gender' => 1, 'birthday' => '1991-08-14', 'email' => 'bob@rebet.local' , 'role' => 'user'],
            ],
        ]);
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