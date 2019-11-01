<?php
namespace Rebet\Tests\Database;

use PDOException;
use Rebet\Common\Arrays;
use Rebet\Database\Dao;
use Rebet\Database\Database;
use Rebet\Database\PdoParameter;
use Rebet\Database\ResultSet;
use Rebet\Database\Statement;
use Rebet\DateTime\Date;
use Rebet\DateTime\DateTime;
use Rebet\Tests\Mock\Enum\Gender;
use Rebet\Tests\Mock\User;
use Rebet\Tests\RebetDatabaseTestCase;

class StatementTest extends RebetDatabaseTestCase
{
    protected function tables(string $db_name) : array
    {
        $db_name = $db_name === 'main' ? 'sqlite' : $db_name ;
        return [
            'sqlite' => [
                'users' => <<<EOS
                    CREATE TABLE IF NOT EXISTS users (
                        user_id INTEGER PRIMARY KEY,
                        name TEXT NOT NULL,
                        gender INTEGER NOT NULL,
                        birthday TEXT NOT NULL,
                        email TEXT NOT NULL,
                        role TEXT NOT NULL DEFAULT 'user',
                        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at TEXT
                    );
EOS
                ,
            ],
            'mysql' => [
                'users' => <<<EOS
                    CREATE TABLE IF NOT EXISTS users (
                        user_id INTEGER PRIMARY KEY,
                        name TEXT NOT NULL,
                        gender INTEGER NOT NULL,
                        birthday DATE NOT NULL,
                        email TEXT NOT NULL,
                        role VARCHAR(6) NOT NULL DEFAULT 'user',
                        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at DATETIME
                    );
EOS
                ,
            ],
            'pgsql' => [
                'users' => <<<EOS
                    CREATE TABLE IF NOT EXISTS users (
                        user_id INTEGER PRIMARY KEY,
                        name TEXT NOT NULL,
                        gender INTEGER NOT NULL,
                        birthday DATE NOT NULL,
                        email TEXT NOT NULL,
                        role TEXT NOT NULL DEFAULT 'user',
                        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP
                    );
EOS
                ,
            ],
        ][$db_name] ?? [];
    }

    protected function records(string $db_name, string $table_name) : array
    {
        return [
            'users' => [
                ['user_id' => 1 , 'name' => 'Elody Bode III' , 'gender' => 2, 'birthday' => '1990-01-08', 'email' => 'elody@s1.rebet.local' , 'role' => 'user'],
                ['user_id' => 2 , 'name' => 'Alta Hegmann'   , 'gender' => 1, 'birthday' => '2003-02-16', 'email' => 'alta_h@s2.rebet.local', 'role' => 'user'],
                ['user_id' => 3 , 'name' => 'Damien Kling'   , 'gender' => 1, 'birthday' => '1992-10-17', 'email' => 'damien@s0.rebet.local', 'role' => 'user'],
            ],
        ][$table_name] ?? [];
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
        $pdo_stmt->method('columnCount')->willThrowException(new PDOException());
        $stmt = new Statement(Dao::db(), $pdo_stmt);
        $this->assertEquals([], $stmt->meta());
    }

    public function test_execute()
    {
        $this->eachDb(function (Database $db) {
            $pdo_stmt = $db->driver()->prepare("SELECT * FROM users", [\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC]);
            $stmt     = new Statement($db, $pdo_stmt);
            $stmt     = $stmt->execute();
            $this->assertInstanceOf(Statement::class, $stmt);
            $rs       = $stmt->all();
            $this->assertSame(3, $rs->count());

            $pdo_stmt = $db->driver()->prepare("SELECT * FROM users WHERE gender = :gender", [\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC]);
            $stmt     = new Statement($db, $pdo_stmt);
            $stmt     = $stmt->execute(['gender' => 1]);
            $this->assertInstanceOf(Statement::class, $stmt);
            $rs       = $stmt->all();
            $this->assertSame(2, $rs->count());

            $pdo_stmt = $db->driver()->prepare("SELECT * FROM users WHERE gender = :gender", [\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC]);
            $stmt     = new Statement($db, $pdo_stmt);
            $stmt     = $stmt->execute(['gender' => PdoParameter::int(2)]);
            $this->assertInstanceOf(Statement::class, $stmt);
            $rs       = $stmt->all();
            $this->assertSame(1, $rs->count());
        });
    }

    /**
     * @expectedException Rebet\Database\Exception\DatabaseException
     * @expectedExceptionMessage [sqlite: UNKOWN] Unkown error occured.
     */
    public function test_execute_exception_01()
    {
        $pdo_stmt = $this->getMockBuilder(\PDOStatement::class)->getMock();
        $pdo_stmt->method('execute')->willThrowException(new PDOException('This is test'));
        $pdo_stmt->method('errorInfo')->willReturn([]);

        $stmt = new Statement(Dao::db(), $pdo_stmt);
        $stmt = $stmt->execute();
    }

    /**
     * @expectedException Rebet\Database\Exception\DatabaseException
     * @expectedExceptionMessage [sqlite: UNKOWN] Unkown error occured.
     */
    public function test_execute_exception_02()
    {
        $pdo_stmt = $this->getMockBuilder(\PDOStatement::class)->getMock();
        $pdo_stmt->method('execute')->willReturn(false);
        $pdo_stmt->method('errorInfo')->willReturn([]);

        $stmt = new Statement(Dao::db(), $pdo_stmt);
        $stmt = $stmt->execute();
    }

    public function test_all()
    {
        $this->eachDb(function (Database $db) {
            $pdo_stmt = $db->driver()->prepare("SELECT * FROM users");
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

            $pdo_stmt = $db->driver()->prepare("SELECT * FROM users");
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
            $pdo_stmt = $db->driver()->prepare("SELECT * FROM users");
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
            $pdo_stmt = $db->driver()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $rs       = $stmt->execute()->allOf('user_id');
            $this->assertInstanceOf(ResultSet::class, $rs);
            $this->assertSame(3, $rs->count());
            $this->assertSame([1, 2, 3], $rs->toArray());

            $rs       = $stmt->allOf('user_id');
            $this->assertTrue($rs->empty());

            $pdo_stmt = $db->driver()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $rs       = $stmt->execute()->allOf(0);
            $this->assertInstanceOf(ResultSet::class, $rs);
            $this->assertSame(3, $rs->count());
            $this->assertSame([1, 2, 3], $rs->toArray());

            $pdo_stmt = $db->driver()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $rs       = $stmt->execute()->allOf('birthday');
            if ($db->driverName() === 'sqlite') {
                $this->assertTrue(is_string($rs[0]));
            } else {
                $this->assertInstanceOf(Date::class, $rs[0]);
            }

            $pdo_stmt = $db->driver()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $rs       = $stmt->execute()->allOf(3);
            if ($db->driverName() === 'sqlite') {
                $this->assertTrue(is_string($rs[0]));
            } else {
                $this->assertInstanceOf(Date::class, $rs[0]);
            }

            $pdo_stmt = $db->driver()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $rs       = $stmt->execute()->allOf('birthday', Date::class);
            $this->assertInstanceOf(Date::class, $rs[0]);

            $pdo_stmt = $db->driver()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $rs       = $stmt->execute()->allOf(3, Date::class);
            $this->assertInstanceOf(Date::class, $rs[0]);

            $pdo_stmt = $db->driver()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $rs       = $stmt->execute()->allOf('birthday', 'string');
            $this->assertTrue(is_string($rs[0]));

            $pdo_stmt = $db->driver()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $rs       = $stmt->execute()->allOf(3, 'string');
            $this->assertTrue(is_string($rs[0]));
        });
    }

    public function test_firstOf()
    {
        $this->eachDb(function (Database $db) {
            $pdo_stmt = $db->driver()->prepare("SELECT * FROM users");
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

            $pdo_stmt = $db->driver()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $rs       = $stmt->execute()->firstOf(0);
            $this->assertTrue(is_int($rs));
            $this->assertSame(1, $rs);

            $pdo_stmt = $db->driver()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $rs       = $stmt->execute()->firstOf('birthday');
            if ($db->driverName() === 'sqlite') {
                $this->assertTrue(is_string($rs));
            } else {
                $this->assertInstanceOf(Date::class, $rs);
            }

            $pdo_stmt = $db->driver()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $rs       = $stmt->execute()->firstOf(3);
            if ($db->driverName() === 'sqlite') {
                $this->assertTrue(is_string($rs));
            } else {
                $this->assertInstanceOf(Date::class, $rs);
            }

            $pdo_stmt = $db->driver()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $rs       = $stmt->execute()->firstOf('birthday', Date::class);
            $this->assertInstanceOf(Date::class, $rs);

            $pdo_stmt = $db->driver()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $rs       = $stmt->execute()->firstOf(3, Date::class);
            $this->assertInstanceOf(Date::class, $rs);

            $pdo_stmt = $db->driver()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $rs       = $stmt->execute()->firstOf('birthday', 'string');
            $this->assertTrue(is_string($rs));

            $pdo_stmt = $db->driver()->prepare("SELECT * FROM users");
            $stmt     = new Statement($db, $pdo_stmt);
            $rs       = $stmt->execute()->firstOf(3, 'string');
            $this->assertTrue(is_string($rs));
        });
    }
}
