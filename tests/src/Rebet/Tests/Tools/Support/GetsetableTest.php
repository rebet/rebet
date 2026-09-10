<?php
namespace Rebet\Tests\Tools\Support;

use Rebet\Tests\RebetTestCase;
use TestApp\Stub\GetsetableStub;

class GetsetableTest extends RebetTestCase
{
    public function test_getset()
    {
        $item = new GetsetableStub();
        $this->assertNull($item->value());
        $this->assertInstanceOf(GetsetableStub::class, $item->value('foo'));
        $this->assertSame('foo', $item->value());
    }
}
