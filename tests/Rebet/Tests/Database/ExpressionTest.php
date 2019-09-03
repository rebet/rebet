<?php
namespace Rebet\Tests\Database;

use Rebet\Database\Expression;
use Rebet\Tests\RebetTestCase;

class ExpressionTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(Expression::class, new Expression('now()'));
    }

    public function test_of()
    {
        $expression = Expression::of('GeomFromText(?)', 'POINT(1 1)');
        $this->assertInstanceOf(Expression::class, $expression);
        $this->assertSame('GeomFromText(?)', $expression->expression);
        $this->assertSame('POINT(1 1)', $expression->value);
    }
}
