<?php
namespace Rebet\Tests\Database\Attribute;

use Rebet\Attribute\AttributedClass;
use Rebet\Database\Attribute\PrimaryKey;
use Rebet\Tests\RebetTestCase;
use TestApp\Stub\AttributedStub;

class PrimaryKeyTest extends RebetTestCase
{
    public function test_attribute()
    {
        $attribute = PrimaryKey::class;
        $ac        = new AttributedClass(AttributedStub::class);

        $a = $ac->property('attributes')->attribute($attribute);
        $this->assertInstanceOf($attribute, $a);
    }
}
