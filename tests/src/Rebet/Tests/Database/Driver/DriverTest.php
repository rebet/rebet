<?php
namespace Rebet\Tests\Database\Driver;

use App\Enum\Gender;
use Rebet\Application\App;
use Rebet\Database\Database;
use Rebet\Database\PdoParameter;
use Rebet\Database\Query;
use Rebet\Tests\RebetDatabaseTestCase;
use Rebet\Tools\DateTime\Date;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\DateTime\DateTimeZone;
use Rebet\Tools\Math\Decimal;

class DriverTest extends RebetDatabaseTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        DateTime::setTestNow('2001-02-03 04:05:06');
    }

    public function dataToPdoTypes() : array
    {
        $this->setUp();
        $path = App::structure()->public('/assets/img/72x72.png');
        $file = file_get_contents($path, 'r');
        return [
            [['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv'], PdoParameter::int(1), PdoParameter::int(1)],
            [['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv'], PdoParameter::int(1), 1],
            [['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv'], PdoParameter::str('a'), 'a'],
            [['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv'], PdoParameter::int(1), Gender::MALE()],
            [['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv'], PdoParameter::null(), null],
            [['sqlite', 'pgsql', 'sqlsrv'], PdoParameter::bool(true), true],
            [['mysql', 'mariadb'], PdoParameter::int(1), true],
            [['sqlite', 'pgsql', 'sqlsrv'], PdoParameter::bool(false), false],
            [['mysql', 'mariadb'], PdoParameter::int(0), false],
            [['sqlite', 'mysql', 'mariadb', 'pgsql'], PdoParameter::lob($file), function () use ($path) { return fopen($path, 'r'); }],
            [['sqlsrv'], PdoParameter::lob($file, \PDO::SQLSRV_ENCODING_BINARY), function () use ($path) { return fopen($path, 'r'); }],
            [['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv'], PdoParameter::str('2001-02-03'), Date::today()],
            [['sqlite', 'mysql', 'mariadb', 'sqlsrv'], PdoParameter::str('2001-02-03 04:05:06'), DateTime::now()],
            [['pgsql'], PdoParameter::str('2001-02-03 04:05:06+0000'), DateTime::now()],
            [['sqlite', 'mysql', 'mariadb', 'sqlsrv'], PdoParameter::str('2001-02-03 04:05:06'), new \DateTime('2001-02-03 04:05:06', new DateTimeZone('Asia/Tokyo'))],
            [['pgsql'], PdoParameter::str('2001-02-03 04:05:06+0900'), new \DateTime('2001-02-03 04:05:06', new DateTimeZone('Asia/Tokyo'))],
            [['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv'], PdoParameter::str('1234.5678'), new Decimal('1,234.5678')],
        ];
    }

    /**
     * @dataProvider dataToPdoTypes
     */
    public function test_toPdoType(array $target_db, PdoParameter $expect, $value)
    {
        $this->eachDb(function (Database $db) use ($target_db, $expect, $value) {
            if (!in_array($db->name(), $target_db)) {
                return;
            }
            if (is_callable($value)) {
                $value = $value();
            }
            $this->assertEquals($expect, $db->driver()->toPdoType($value));
        });
    }

    public function dataAppendLimitOffers() : array
    {
        return [
            ["SELECT * FROM users", "SELECT * FROM users", null, null],
            ["SELECT * FROM users", "SELECT * FROM users", null, null, ['sqlsrv']],

            ["SELECT * FROM users LIMIT 10", "SELECT * FROM users", 10, null],
            ["SELECT TOP 10 * FROM users"  , "SELECT * FROM users", 10, null, ['sqlsrv']],
            ["SELECT TOP 10 * FROM (SELECT * FROM users) AS T"  , "SELECT * FROM (SELECT * FROM users) AS T", 10, null, ['sqlsrv']],
            ["/* SELECT */ SELECT TOP 10 * FROM (SELECT * FROM users) AS T"  , "/* SELECT */ SELECT * FROM (SELECT * FROM users) AS T", 10, null, ['sqlsrv']],
            ["/* SELECT \n SELECT */ SELECT TOP 10 * FROM (SELECT * FROM users) AS T"  , "/* SELECT \n SELECT */ SELECT * FROM (SELECT * FROM users) AS T", 10, null, ['sqlsrv']],
            ["-- SELECT\nSELECT TOP 10 * FROM (SELECT * FROM users) AS T"  , "-- SELECT\nSELECT * FROM (SELECT * FROM users) AS T", 10, null, ['sqlsrv']],
            ["-- SELECT\n-- SELECT\nSELECT TOP 10 * FROM (SELECT * FROM users) AS T"  , "-- SELECT\n-- SELECT\nSELECT * FROM (SELECT * FROM users) AS T", 10, null, ['sqlsrv']],
            ["/* SELECT */\n-- SELECT\n SELECT TOP 10 * FROM (SELECT * FROM users) AS T"  , "/* SELECT */\n-- SELECT\n SELECT * FROM (SELECT * FROM users) AS T", 10, null, ['sqlsrv']],
            ["-- SELECT\n /* SELECT */ SELECT TOP 10 * FROM (SELECT * FROM users) AS T"  , "-- SELECT\n /* SELECT */ SELECT * FROM (SELECT * FROM users) AS T", 10, null, ['sqlsrv']],
            ["/* SELECT */\n /* SELECT */ SELECT TOP 10 * FROM (SELECT * FROM users) AS T"  , "/* SELECT */\n /* SELECT */ SELECT * FROM (SELECT * FROM users) AS T", 10, null, ['sqlsrv']],
            ["-- SELECT\n /* SELECT */\n-- SELECT\n /* SELECT */ SELECT TOP 10 * FROM (SELECT * FROM users) AS T"  , "-- SELECT\n /* SELECT */\n-- SELECT\n /* SELECT */ SELECT * FROM (SELECT * FROM users) AS T", 10, null, ['sqlsrv']],

            ["SELECT * FROM users OFFSET 10", "SELECT * FROM users", null, 10],
            ["SELECT * FROM users OFFSET 10 ROWS", "SELECT * FROM users", null, 10, ['sqlsrv']],
            ["SELECT * FROM users ORDER BY user_id OFFSET 10 ROWS", "SELECT * FROM users ORDER BY user_id", null, 10, ['sqlsrv']],

            ["SELECT * FROM users LIMIT 10 OFFSET 100", "SELECT * FROM users", 10, 100],
            ["SELECT * FROM users OFFSET 100 ROWS FETCH NEXT 10 ROWS ONLY", "SELECT * FROM users", 10, 100, ['sqlsrv']],
            ["SELECT * FROM users ORDER BY user_id OFFSET 100 ROWS FETCH NEXT 10 ROWS ONLY", "SELECT * FROM users ORDER BY user_id", 10, 100, ['sqlsrv']],
        ];
    }

    /**
     * @dataProvider dataAppendLimitOffers
     */
    public function test_appendLimitOffset(string $expect, string $sql, ?int $limit = null, ?int $offset = null, array $dbs = ['sqlite', 'mysql', 'mariadb', 'pgsql'])
    {
        $this->eachDb(function (Database $db) use ($expect, $sql, $limit, $offset) {
            $this->assertSame($expect, $db->driver()->appendLimitOffset($sql, $limit, $offset));
        }, ...$dbs);
    }

    public function test_sql()
    {
        $this->eachDb(function (Database $db) {
            $query = $db->driver()->sql("SELECT * FROM users WHERE gender = :gender", ['gender' => 1]);
            $this->assertInstanceOf(Query::class, $query);
            $this->assertSame("SELECT * FROM users WHERE gender = :gender", $query->sql());
            $this->assertEquals(['gender' => 1], $query->params());
        });
    }
}
