<?php
namespace Rebet\Tests\Database;

use Rebet\Database\Expression;
use Rebet\Database\PdoParameter;
use Rebet\Tests\RebetDatabaseTestCase;

class ExpressionTest extends RebetDatabaseTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(Expression::class, new Expression('now()'));
    }

    public function test_of()
    {
        $expression = Expression::of('GeomFromText({0})', 'POINT(1 1)');
        $this->assertInstanceOf(Expression::class, $expression);
    }

    public function dataCompiles() : array
    {
        return [
            ['now()', [], ':foo', Expression::of('now()')],
            [
                'GeomFromText(:foo__0)',
                [
                    ':foo__0' => PdoParameter::str('POINT(1 1)')
                ],
                ':foo',
                Expression::of('GeomFromText({0})', 'POINT(1 1)')
            ],
            [
                'geometry::STGeomFromText(:foo__0, :foo__1)',
                [
                    ':foo__0' => PdoParameter::str('LINESTRING (100 100, 20 180, 180 180)'),
                    ':foo__1' => PdoParameter::int(0)
                ],
                ':foo',
                Expression::of('geometry::STGeomFromText({0}, {1})', 'LINESTRING (100 100, 20 180, 180 180)', 0)
            ],
        ];
    }

    /**
     * @dataProvider dataCompiles
     */
    public function test_compile($expect_sql, $expect_params, $placeholder, Expression $expression)
    {
        $query = $expression->compile($this->connect('mysql')->driver(), $placeholder);
        $this->assertSame($expect_sql, $query->sql());
        $this->assertEquals($expect_params, $query->params());
    }
}
