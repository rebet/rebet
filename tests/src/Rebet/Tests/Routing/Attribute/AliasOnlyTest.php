<?php
namespace Rebet\Tests\Routing\Attribute;

use App\Stub\AttributedStub;
use Rebet\Attribute\AttributedClass;
use Rebet\Routing\Attribute\AliasOnly;
use Rebet\Tests\RebetTestCase;

class AliasOnlyTest extends RebetTestCase
{
    public function test_attribute()
    {
        $attribute = AliasOnly::class;
        $ac        = new AttributedClass(AttributedStub::class);

        $a = $ac->attribute($attribute);
        $this->assertInstanceOf($attribute, $a);

        $a = $ac->method('attributes')->attribute($attribute);
        $this->assertInstanceOf($attribute, $a);
    }
}
