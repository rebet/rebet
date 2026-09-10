<?php
namespace Rebet\Tests\Routing\Attribute;

use Rebet\Attribute\AttributedClass;
use Rebet\Routing\Attribute\Where;
use Rebet\Tests\RebetTestCase;
use TestApp\Stub\AttributedStub;

class WhereTest extends RebetTestCase
{
    public function test_attribute()
    {
        $attribute = Where::class;
        $ac        = new AttributedClass(AttributedStub::class);

        $a = $ac->attribute($attribute);
        $this->assertInstanceOf($attribute, $a);
        $this->assertSame(["id" => "[0-9]+"], $a->wheres);

        $a = $ac->method('attributes')->attribute($attribute);
        $this->assertInstanceOf($attribute, $a);
        $this->assertSame(["seq" => "[0-9]+", "code" => "[a-zA-Z]+"], $a->wheres);
    }
}
