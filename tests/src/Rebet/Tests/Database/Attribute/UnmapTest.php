<?php
namespace Rebet\Tests\Database\Attribute;

use App\Stub\AttributedStub;
use Rebet\Attribute\AttributedClass;
use Rebet\Database\Attribute\Unmap;
use Rebet\Tests\RebetTestCase;

class UnmapTest extends RebetTestCase
{
    public function test_attribute()
    {
        $attribute = Unmap::class;
        $ac        = new AttributedClass(AttributedStub::class);

        $a = $ac->property('attributes')->attribute($attribute);
        $this->assertInstanceOf($attribute, $a);
    }
}
