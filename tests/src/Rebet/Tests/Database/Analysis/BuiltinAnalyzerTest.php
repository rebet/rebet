<?php
namespace Rebet\Tests\Database\Analysis;

use Rebet\Database\Analysis\BuiltinAnalyzer;
use Rebet\Tests\RebetDatabaseTestCase;

class BuiltinAnalyzerTest extends RebetDatabaseTestCase
{
    public function dataIsUnions() : array
    {
        return [
            [false, "SELECT * FROM users"],
            [true , "SELECT 1 as foo FROM bar UNION SELECT 2 as foo FROM baz"],
            [true , "SELECT 1 as foo FROM bar UNION ALL SELECT 2 as foo FROM baz"],
            [false, "SELECT * FROM (SELECT 1 as foo FROM bar UNION ALL SELECT 2 as foo FROM baz) AS T"],
        ];
    }

    /**
     * @dataProvider dataIsUnions
     */
    public function test_isUnion(bool $expect, string $sql)
    {
        $analyser = new BuiltinAnalyzer($sql);
        $this->assertSame($expect, $analyser->isUnion());
    }

    public function dataHasWheres() : array
    {
        return [
            [false , "SELECT * FROM users"],
            [true  , "SELECT * FROM users WHERE gender = 1"],
            [false , "SELECT * FROM users ORDER BY created_at"],
            [true  , "SELECT * FROM users WHERE gender = 1 ORDER BY created_at"],
            [false , "SELECT * FROM (SELECT * FROM users WHERE gender = 1) AS T"],
            [false , "SELECT (SELECT max(user_id) FROM users WHERE gender = 1) AS max_male_user_id"],
        ];
    }

    /**
     * @dataProvider dataHasWheres
     */
    public function test_hasWhere(bool $expect, string $sql)
    {
        $analyser = new BuiltinAnalyzer($sql);
        $this->assertSame($expect, $analyser->hasWhere());
    }

    public function dataHasHavings() : array
    {
        return [
            [false , "SELECT * FROM users"],
            [true  , "SELECT * FROM users HAVING gender = 1"],
            [false , "SELECT * FROM users ORDER BY created_at"],
            [true  , "SELECT * FROM users HAVING gender = 1 ORDER BY created_at"],
            [false , "SELECT * FROM (SELECT * FROM users HAVING gender = 1) AS T"],
            [false , "SELECT (SELECT max(user_id) FROM users HAVING gender = 1) AS max_male_user_id"],
        ];
    }

    /**
     * @dataProvider dataHasHavings
     */
    public function test_hasHaving(bool $expect, string $sql)
    {
        $analyser = new BuiltinAnalyzer($sql);
        $this->assertSame($expect, $analyser->hasHaving());
    }

    public function dataHasGroupBys() : array
    {
        return [
            [false , "SELECT * FROM users"],
            [false , "SELECT * FROM users WHERE name = 'GROUP BY'"],
            [true  , "SELECT gender, count(*) AS count FROM users GROUP BY gender"],
            [false , "SELECT * FROM (SELECT gender, count(*) AS count FROM users GROUP BY gender) AS T"],
        ];
    }

    /**
     * @dataProvider dataHasGroupBys
     */
    public function test_hasGroupBy(bool $expect, string $sql)
    {
        $analyser = new BuiltinAnalyzer($sql);
        $this->assertSame($expect, $analyser->hasGroupBy());
    }

    public function dataHasOrderBys() : array
    {
        return [
            [false , "SELECT * FROM users"],
            [false , "SELECT * FROM users WHERE name = 'ORDER BY'"],
            [true  , "SELECT gender, count(*) AS count FROM users ORDER BY gender"],
            [false , "SELECT * FROM (SELECT gender, count(*) AS count FROM users ORDER BY gender) AS T"],
        ];
    }

    /**
     * @dataProvider dataHasOrderBys
     */
    public function test_hasOrderBy(bool $expect, string $sql)
    {
        $analyser = new BuiltinAnalyzer($sql);
        $this->assertSame($expect, $analyser->hasOrderBy());
    }

    public function dataExtractAliasSelectColumns() : array
    {
        return [
            ['user_id', "SELECT * FROM users", 'user_id'],
            ['user_id', "SELECT * FROM users WHERE gender = 1", 'user_id'],
            ['user_id', "SELECT user_id FROM users", 'user_id'],
            ['user_id', "SELECT user_id as id FROM users", 'id'],
            ['user_id', "SELECT user_id id FROM users", 'id'],
            ['id', "SELECT user_id FROM users", 'id'],
            ['1', "SELECT 1 as foo FROM users", 'foo'],
            ["'foo'", "SELECT 'foo' as foo FROM users", 'foo'],
            ['now()', "SELECT now() as foo FROM users", 'foo'],
            ['(1 + 2)', "SELECT (1 + 2) as foo FROM users", 'foo'],
            ['1 + 2', "SELECT 1 + 2 as foo FROM users", 'foo'],
            ['CURRENT_TIMESTAMP', "SELECT CURRENT_TIMESTAMP as foo FROM users", 'foo'],
            ['COALESCE(updated_at,created_at)', "SELECT COALESCE(updated_at, created_at) as change_at FROM users", 'change_at'],
            ['COUNT(*)', "SELECT gender, COUNT(*) as count, AVG(age) as average_age FROM users GROUP BY gender", 'count'],
            ['AVG(age)', "SELECT gender, COUNT(*) as count, AVG(age) as average_age FROM users GROUP BY gender", 'average_age'],
            ["CASE gender WHEN 1 THEN 'male' ELSE 'female' END", "SELECT CASE gender WHEN 1 THEN 'male' ELSE 'female' END as gender_label FROM users", 'gender_label'],
            ['(SELECT MAX(created_at) AS latest_created_at FROM users AS T WHERE T.birthday = U.birthday)', "SELECT *, (SELECT MAX(created_at) as latest_created_at FROM users AS T WHERE T.birthday = U.birthday) AS latest_created_at FROM users AS U WHERE gender = 1", 'latest_created_at'],
            ['foo', "SELECT 1 as foo FROM bar UNION SELECT 2 as foo FROM baz", 'foo'],
            ['foo', "SELECT 1 as foo FROM bar UNION ALL SELECT 2 as foo FROM baz", 'foo'],
        ];
    }

    /**
     * @dataProvider dataExtractAliasSelectColumns
     */
    public function test_extractAliasSelectColumn(string $expect, string $sql, string $alias)
    {
        $analyser = new BuiltinAnalyzer($sql);
        $this->assertSame($expect, $analyser->extractAliasSelectColumn($alias));
    }
}
