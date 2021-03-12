<?php
namespace Rebet\Tests\Tools\Support;

use App\Stub\GetsetableStub;
use Rebet\Tests\RebetTestCase;

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
