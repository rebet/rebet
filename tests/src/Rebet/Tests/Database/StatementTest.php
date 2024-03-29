<?php
namespace Rebet\Tests\Database;

use App\Model\User;
use App\Enum\Gender;
use Rebet\Database\Dao;
use Rebet\Database\Database;
use Rebet\Database\Exception\DatabaseException;
use Rebet\Database\PdoParameter;
use Rebet\Database\ResultSet;
use Rebet\Database\Statement;
use Rebet\Tests\RebetDatabaseTestCase;
use Rebet\Tools\DateTime\Date;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\Utility\Arrays;

class StatementTest extends RebetDatabaseTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        $this->setUpDataSet([
            'users' => [
                ['user_id' , 'name'           , 'gender' , 'birthday'   , 'email'                 , 'role' , 'password'                                                     , 'api_token'                                                        ],
                // ------- | ---------------- | -------- | ------------ | ----------------------- | ------ | -------------------------------------------------------------- | ----------------------------------------------------------------- //
                [        1 , 'Elody Bode III' ,        2 , '1990-01-08' , 'elody@s1.rebet.local'  , 'user' , '$2y$10$iUQ0l38dqjdf.L7OeNpyNuzmYf5qPzXAUwyKhC3G0oqTuUAO5ouci' , 'fe0c1b9ca200d6e01d96f60bab714cbbaffdf89fed5a946ff1b9f024902d2a26' ], // password-{user_id}, api-{user_id}
                [        2 , 'Alta Hegmann'   ,        1 , '2003-02-16' , 'alta_h@s2.rebet.local' , 'user' , '$2y$10$xpouw11HAUb3FAEBXYcwm.kcGmF0.FetTqkQQJFiShY2TiVCwEAQW' , '3d9b9b04a60382dd0f0acb2672b3b87acba7e9a9e44c529ba37baebe1cf9a00c' ], // password-{user_id}, api-{user_id}
                [        3 , 'Damien Kling'   ,        1 , '1992-10-17' , 'damien@s0.rebet.local' , 'user' , '$2y$10$ciYenJCNJh/rKRy9GRNTIO5HQwP0N2t0Hb5db2ESj8Veaty/TjJCe' , 'df38d2697f917ca9460677a98bfbb8baaeabab8e83b9858ea70d6da10b06ad4d' ], // password-{user_id}, api-{user_id}
            ],
        ]);
    }

    public function test___construct()
    {
        $pdo_stmt = $this->getMockBuilder(\PDOStatement::class)->getMock();
        $this->assertInstanceOf(Statement::class, new Statement(Dao::db(), $pdo_stmt));
    }

    public function test_raw()
    {
        $pdo_stmt = $this->getMockBuilder(\PDOStatement::class)->getMock();
        $stmt     = new Statement(Dao::db(), $pdo_stmt);
        $this->assertSame($pdo_stmt, $stmt->raw());
    }

    public function test_meta()
    {
        $pdo_stmt = $this->getMockBuilder(\PDOStatement::class)->getMock();
        $pdo_stmt->method('columnCount')->willReturn(2);
        $pdo_stmt->method('getColumnMeta')->will($this->returnValueMap([
            [0, ['name' => 'foo', 'native_type' => 'int']],
            [1, ['name' => 'bar', 'native_type' => 'string']]
        ]));
        $stmt = new Statement(Dao::db(), $pdo_stmt);
        $this->assertEquals([
            'foo' => ['name' => 'foo', 'native_type' => 'int'],    0 => ['name' => 'foo', 'native_type' => 'int'],
            'bar' => ['name' => 'bar', 'native_type' => 'string'], 1 => ['name' => 'bar', 'native_type' => 'string'],
        ], $stmt->meta());


        $pdo_stmt = $this->getMockBuilder(\PDOStatement::class)->getMock();
        $pdo_stmt->method('columnCount')->willThrowException(new \PDOException());
        $stmt = new Statement(Dao::db(), $pdo_stmt);
        $this->assertEquals([], $stmt->meta());
    }

    public function test_execute()
    {
        $this->eachDb(function (Database $db) {
            $pdo_stmt = $db->pdo()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $stmt     = $stmt->execute();
            $this->assertInstanceOf(Statement::class, $stmt);
            $rs       = $stmt->all();
            $this->assertSame(3, $rs->count());

            $pdo_stmt = $db->pdo()->prepare("SELECT * FROM users WHERE gender = :gender");
            $stmt     = new Statement($db, $pdo_stmt);
            $stmt     = $stmt->execute(['gender' => 1]);
            $this->assertInstanceOf(Statement::class, $stmt);
            $rs       = $stmt->all();
            $this->assertSame(2, $rs->count());

            $pdo_stmt = $db->pdo()->prepare("SELECT * FROM users WHERE gender = :gender");
            $stmt     = new Statement($db, $pdo_stmt);
            $stmt     = $stmt->execute(['gender' => PdoParameter::int(2)]);
            $this->assertInstanceOf(Statement::class, $stmt);
            $rs       = $stmt->all();
            $this->assertSame(1, $rs->count());
        });
    }

    public function test_execute_exception_01()
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage("[db:sqlite/-----] This is test");

        $pdo_stmt = $this->getMockBuilder(\PDOStatement::class)->getMock();
        $pdo_stmt->method('execute')->willThrowException(new \PDOException('This is test'));
        $pdo_stmt->method('errorInfo')->willReturn([]);

        $stmt = new Statement(Dao::db(), $pdo_stmt);
        $stmt = $stmt->execute();
    }

    public function test_execute_exception_02()
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage("[db:sqlite/-----] Unkown error occured.");

        $pdo_stmt = $this->getMockBuilder(\PDOStatement::class)->getMock();
        $pdo_stmt->method('execute')->willReturn(false);
        $pdo_stmt->method('errorInfo')->willReturn([]);

        $stmt = new Statement(Dao::db(), $pdo_stmt);
        $stmt = $stmt->execute();
    }

    public function test_all()
    {
        $this->eachDb(function (Database $db) {
            $pdo_stmt = $db->pdo()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $rs       = $stmt->execute()->all();
            $this->assertInstanceOf(ResultSet::class, $rs);
            $this->assertSame(3, $rs->count());
            $this->assertSame([1, 2, 3], Arrays::pluck($rs->toArray(), 'user_id'));
            $this->assertInstanceOf('stdClass', $rs[0]);
            $this->assertTrue(is_int($rs[0]->gender));
            if ($db->driverName() === 'sqlite') {
                $this->assertTrue(is_string($rs[0]->birthday));
                $this->assertTrue(is_string($rs[0]->created_at));
            } else {
                $this->assertInstanceOf(Date::class, $rs[0]->birthday);
                $this->assertInstanceOf(DateTime::class, $rs[0]->created_at);
            }

            $rs       = $stmt->all();
            $this->assertTrue($rs->empty());

            $pdo_stmt = $db->pdo()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $rs       = $stmt->execute()->all(User::class);
            $this->assertInstanceOf(ResultSet::class, $rs);
            $this->assertSame(3, $rs->count());
            $this->assertSame([1, 2, 3], Arrays::pluck($rs->toArray(), 'user_id'));
            $this->assertInstanceOf(User::class, $rs[0]);
            $this->assertInstanceOf(Gender::class, $rs[0]->gender);
            $this->assertInstanceOf(Date::class, $rs[0]->birthday);
            $this->assertInstanceOf(DateTime::class, $rs[0]->created_at);
        });
    }

    public function test_first()
    {
        $this->eachDb(function (Database $db) {
            $pdo_stmt = $db->pdo()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $rs       = $stmt->execute()->first();
            $this->assertInstanceOf('stdClass', $rs);
            $this->assertSame(1, $rs->user_id);
            $this->assertTrue(is_int($rs->gender));
            if ($db->driverName() === 'sqlite') {
                $this->assertTrue(is_string($rs->birthday));
                $this->assertTrue(is_string($rs->created_at));
            } else {
                $this->assertInstanceOf(Date::class, $rs->birthday);
                $this->assertInstanceOf(DateTime::class, $rs->created_at);
            }

            $rs = $stmt->first();
            $this->assertSame(2, $rs->user_id);

            $rs = $stmt->first(User::class);
            $this->assertSame(3, $rs->user_id);
            $this->assertInstanceOf(User::class, $rs);
            $this->assertInstanceOf(Gender::class, $rs->gender);
            $this->assertInstanceOf(Date::class, $rs->birthday);
            $this->assertInstanceOf(DateTime::class, $rs->created_at);

            $rs = $stmt->first();
            $this->assertNull($rs);
        });
    }

    public function test_allOf()
    {
        $this->eachDb(function (Database $db) {
            $pdo_stmt = $db->pdo()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $rs       = $stmt->execute()->allOf('user_id');
            $this->assertInstanceOf(ResultSet::class, $rs);
            $this->assertSame(3, $rs->count());
            $this->assertSame([1, 2, 3], $rs->toArray());

            $rs       = $stmt->allOf('user_id');
            $this->assertTrue($rs->empty());

            $pdo_stmt = $db->pdo()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $rs       = $stmt->execute()->allOf(0);
            $this->assertInstanceOf(ResultSet::class, $rs);
            $this->assertSame(3, $rs->count());
            $this->assertSame([1, 2, 3], $rs->toArray());

            $pdo_stmt = $db->pdo()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $rs       = $stmt->execute()->allOf('birthday');
            if ($db->driverName() === 'sqlite') {
                $this->assertTrue(is_string($rs[0]));
            } else {
                $this->assertInstanceOf(Date::class, $rs[0]);
            }

            $pdo_stmt = $db->pdo()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $rs       = $stmt->execute()->allOf(3);
            if ($db->driverName() === 'sqlite') {
                $this->assertTrue(is_string($rs[0]));
            } else {
                $this->assertInstanceOf(Date::class, $rs[0]);
            }

            $pdo_stmt = $db->pdo()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $rs       = $stmt->execute()->allOf('birthday', Date::class);
            $this->assertInstanceOf(Date::class, $rs[0]);

            $pdo_stmt = $db->pdo()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $rs       = $stmt->execute()->allOf(3, Date::class);
            $this->assertInstanceOf(Date::class, $rs[0]);

            $pdo_stmt = $db->pdo()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $rs       = $stmt->execute()->allOf('birthday', 'string');
            $this->assertTrue(is_string($rs[0]));

            $pdo_stmt = $db->pdo()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $rs       = $stmt->execute()->allOf(3, 'string');
            $this->assertTrue(is_string($rs[0]));
        });
    }

    public function test_firstOf()
    {
        $this->eachDb(function (Database $db) {
            $pdo_stmt = $db->pdo()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $rs       = $stmt->execute()->firstOf('user_id');
            $this->assertTrue(is_int($rs));
            $this->assertSame(1, $rs);

            $rs = $stmt->firstOf('user_id');
            $this->assertSame(2, $rs);

            $rs = $stmt->firstOf('user_id', 'string');
            $this->assertSame('3', $rs);

            $rs = $stmt->firstOf('user_id');
            $this->assertNull($rs);

            $pdo_stmt = $db->pdo()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $rs       = $stmt->execute()->firstOf(0);
            $this->assertTrue(is_int($rs));
            $this->assertSame(1, $rs);

            $pdo_stmt = $db->pdo()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $rs       = $stmt->execute()->firstOf('birthday');
            if ($db->driverName() === 'sqlite') {
                $this->assertTrue(is_string($rs));
            } else {
                $this->assertInstanceOf(Date::class, $rs);
            }

            $pdo_stmt = $db->pdo()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $rs       = $stmt->execute()->firstOf(3);
            if ($db->driverName() === 'sqlite') {
                $this->assertTrue(is_string($rs));
            } else {
                $this->assertInstanceOf(Date::class, $rs);
            }

            $pdo_stmt = $db->pdo()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $rs       = $stmt->execute()->firstOf('birthday', Date::class);
            $this->assertInstanceOf(Date::class, $rs);

            $pdo_stmt = $db->pdo()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $rs       = $stmt->execute()->firstOf(3, Date::class);
            $this->assertInstanceOf(Date::class, $rs);

            $pdo_stmt = $db->pdo()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $rs       = $stmt->execute()->firstOf('birthday', 'string');
            $this->assertTrue(is_string($rs));

            $pdo_stmt = $db->pdo()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $rs       = $stmt->execute()->firstOf(3, 'string');
            $this->assertTrue(is_string($rs));
        });
    }

    public function test_affectedRows()
    {
        $this->eachDb(function (Database $db, $driver) {
            $pdo_stmt = $db->pdo()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $count    = $stmt->execute()->affectedRows();

            switch ($driver) {
                case 'sqlite': $this->assertSame(0, $count, 'on DB '.$driver); break;
                case 'sqlsrv': $this->assertSame(-1, $count, 'on DB '.$driver); break;
                default: $this->assertSame(3, $count, 'on DB '.$driver);
            }

            $pdo_stmt = $db->pdo()->prepare("INSERT INTO users VALUES(99, 'foo', 2, '1980-01-02', 'foo@bar.rebet.local', 'user', 'dummy', 'dummy', CURRENT_TIMESTAMP, NULL)");
            $stmt     = new Statement($db, $pdo_stmt);
            $count    = $stmt->execute()->affectedRows();
            $this->assertSame(1, $count);

            $pdo_stmt = $db->pdo()->prepare("UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE gender = 1");
            $stmt     = new Statement($db, $pdo_stmt);
            $count    = $stmt->execute()->affectedRows();
            $this->assertSame(2, $count);
        });
    }

    public function test_each()
    {
        $this->eachDb(function (Database $db) {
            $pdo_stmt = $db->pdo()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $i = 1;
            $stmt->execute()->each(function (User $user) use (&$i) {
                $this->assertEquals($i++, $user->user_id);
                $this->assertInstanceOf(User::class, $user);
            });

            $pdo_stmt = $db->pdo()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $stmt->execute()->each(function ($user) {
                $this->assertEquals(1, $user->user_id);
                $this->assertInstanceOf('stdClass', $user);
                return false;
            });
        });
    }

    public function test_filter()
    {
        $this->eachDb(function (Database $db) {
            $pdo_stmt = $db->pdo()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $users    = $stmt->execute()->filter(function (User $user) {
                return $user->gender == Gender::MALE();
            });
            $this->assertSame([2, 3], Arrays::pluck($users->toArray(), 'user_id'));
        });
    }

    public function test_map()
    {
        $this->eachDb(function (Database $db) {
            $pdo_stmt = $db->pdo()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $users    = $stmt->execute()->map(function (User $user) {
                $user->name = $user->gender == Gender::MALE() ? "Mr. {$user->name}" : "Ms. {$user->name}";
                return $user;
            });
            $this->assertSame(['Ms. Elody Bode III', 'Mr. Alta Hegmann', 'Mr. Damien Kling'], Arrays::pluck($users->toArray(), 'name'));
        });
    }

    public function test_reduce()
    {
        $this->eachDb(function (Database $db) {
            $pdo_stmt = $db->pdo()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $result   = $stmt->execute()->reduce(function (User $user, $carry) {
                return $carry + $user->user_id;
            }, 0);
            $this->assertSame(6, $result);
        });
    }

    public function test_getIterator()
    {
        $this->eachDb(function (Database $db) {
            $pdo_stmt = $db->pdo()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $this->assertInstanceOf(\Traversable::class, $stmt->getIterator());
        });
    }

    public function test_close()
    {
        $this->eachDb(function (Database $db) {
            $pdo_stmt = $db->pdo()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $this->assertTrue($stmt->execute()->close());
        });
    }
}
