<?php
namespace Rebet\Tests\Database;

use Rebet\Database\Condition;
use Rebet\Tests\RebetTestCase;

class ConditionTest extends RebetTestCase
{
    public function test___construct()
    {
        $condition = new Condition('param = :param', ['param' => 'value']);
        $this->assertInstanceOf(Condition::class, $condition);
    }

    public function test_where()
    {
        $condition = new Condition('');
        $this->assertEquals('', $condition->where());

        $condition = new Condition('param = :param', ['param' => 'value']);
        $this->assertEquals(' WHERE param = :param', $condition->where());
    }
}
