<?php
namespace Rebet\Tests\Database\Ransack;

use Rebet\Database\Database;
use Rebet\Database\Query;
use Rebet\Database\Ransack\BuiltinRansacker;
use Rebet\Tests\Mock\Enum\Gender;
use Rebet\Tests\RebetDatabaseTestCase;

class BuiltinRansackerTest extends RebetDatabaseTestCase
{
    public function test___construct()
    {
        $this->eachDb(function (Database $db) {
            $this->assertInstanceOf(BuiltinRansacker::class, new BuiltinRansacker($db->driver()));
        });
    }

    public function test_of()
    {
        $this->eachDb(function (Database $db) {
            $this->assertInstanceOf(BuiltinRansacker::class, BuiltinRansacker::of($db->driver()));
        });
    }

    public function test_resolve()
    {
        $this->eachDb(function (Database $db) {
            $driver = $db->driver();
            $this->assertEquals(new Query($driver, $driver->quoteIdentifier('name').' = :name', ['name' => 'foo']), BuiltinRansacker::of($driver)->resolve('name', 'foo'));
        });
    }

    public function dataBuilds() : array
    {
        return [
            [
                '?name? = :name',
                ['name' => 'foo'],
                ['name' => 'foo'],
            ],
            [
                '?name? = :name AND ?gender? = :gender',
                ['name' => 'foo', 'gender' => Gender::MALE()],
                ['name' => 'foo', 'gender' => Gender::MALE()],
            ],
            [
                '?name? = :name',
                ['name' => 'foo'],
                ['name' => 'foo', 'gender' => null],
            ],
            [
                '?name? = :name AND ((?gender? = :gender_0 AND ?age? > :age_gt_0) OR (?gender? = :gender_1 AND ?age? <= :age_lteq_1))',
                ['name' => 'foo', 'gender_0' => 1, 'age_gt_0' => 20, 'gender_1' => 2, 'age_lteq_1' => 19],
                ['name' => 'foo', [['gender' => 1, 'age_gt' => 20], ['gender' => 2, 'age_lteq' => 19]]],
            ],
            [
                '?name? = :name AND ?resign_at? IS NOT NULL',
                ['name' => 'foo'],
                ['name' => 'foo', 'resign_at_not_null' => 1],
            ],
            [
                '(?first_name? = :name_0 OR ?last_name? = :name_1) AND ?gender? = :gender',
                ['name_0' => 'foo', 'name_1' => 'foo', 'gender' => 1],
                ['name'   => 'foo', 'gender' => 1],
                ['name'   => ['first_name', 'last_name']]
            ],
        ];
    }

    /**
     * @dataProvider dataBuilds
     */
    public function test_build($expect_sql, $expect_params, $ransack, $alias = [], $extention = null, $dbs = [])
    {
        $this->eachDb(function (Database $db) use ($expect_sql, $expect_params, $ransack, $alias, $extention) {
            $condition = BuiltinRansacker::of($db->driver())->build($ransack, $alias, $extention);
            $this->assertwildcardString($expect_sql, $condition->sql());
            $this->assertEquals($expect_params, $condition->params());
        }, ...$dbs);
    }
}
