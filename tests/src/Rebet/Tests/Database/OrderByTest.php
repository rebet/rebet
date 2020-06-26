<?php
namespace Rebet\Tests\Database;

use Rebet\Database\OrderBy;
use Rebet\Tests\RebetTestCase;

class OrderByTest extends RebetTestCase
{
    public function test___construct()
    {
        $order_by = new OrderBy(['foo' => 'asc', 'bar' => 'desc']);
        $this->assertInstanceOf(OrderBy::class, $order_by);
        $this->assertSame('ASC', $order_by['foo']);
        $this->assertSame('DESC', $order_by['bar']);
    }

    public function test_reverse()
    {
        $order_by = (new OrderBy(['foo' => 'asc', 'bar' => 'desc']))->reverse();
        $this->assertInstanceOf(OrderBy::class, $order_by);
        $this->assertSame('DESC', $order_by['foo']);
        $this->assertSame('ASC', $order_by['bar']);
    }

    public function test_valueOf()
    {
        $order_by = new OrderBy(['foo' => 'asc', 'bar' => 'desc']);
        $this->assertNull(OrderBy::valueOf(null));
        $this->assertInstanceOf(OrderBy::class, OrderBy::valueOf($order_by));
        $this->assertInstanceOf(OrderBy::class, OrderBy::valueOf(['foo' => 'asc', 'bar' => 'desc']));
        $this->assertNull(OrderBy::valueOf('invalid'));
    }
}
