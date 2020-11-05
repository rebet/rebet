<?php
namespace Rebet\Tests\Database;

use Rebet\Database\ResultSet;
use Rebet\Tests\RebetTestCase;

class ResultSetTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(ResultSet::class, new ResultSet([]));
        $this->assertEquals([], (new ResultSet([]))->toArray());
        $this->assertEquals([1], (new ResultSet(1))->toArray());
        $this->assertEquals([1, 2, 3], (new ResultSet([1, 2, 3]))->toArray());
    }

    public function test_reverse()
    {
        $rs = new ResultSet([1, 2, 3]);
        $this->assertEquals([1, 2, 3], $rs->toArray());
        $rs->reverse();
        $this->assertEquals([3, 2, 1], $rs->toArray());
    }

    public function test_pluk()
    {
        $this->assertEquals([1, 2, 3], (new ResultSet([['a' => 1], ['a' => 2], ['a' => 3]]))->pluk('a'));
    }
}
