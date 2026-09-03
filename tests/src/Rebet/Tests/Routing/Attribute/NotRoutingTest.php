<?php
namespace Rebet\Tests\Routing\Attribute;

use App\Stub\AttributedStub;
use Rebet\Attribute\AttributedClass;
use Rebet\Routing\Attribute\NotRouting;
use Rebet\Tests\RebetTestCase;

class NotRoutingTest extends RebetTestCase
{
    public function test_attribute()
    {
        $attribute = NotRouting::class;
        $ac        = new AttributedClass(AttributedStub::class);

        $a = $ac->method('attributes')->attribute($attribute);
        $this->assertInstanceOf($attribute, $a);

        $a = $ac->method('noAttributes')->attribute($attribute, false);
        $this->assertNull($a);
    }
}
