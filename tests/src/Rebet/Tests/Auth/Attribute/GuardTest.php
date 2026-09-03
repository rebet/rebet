<?php
namespace Rebet\Tests\Auth\Attribute;

use App\Stub\AttributedStub;
use Rebet\Attribute\AttributedClass;
use Rebet\Auth\Attribute\Guard;
use Rebet\Tests\RebetTestCase;

class GuardTest extends RebetTestCase
{
    public function test_attribute()
    {
        $attribute = Guard::class;
        $ac        = new AttributedClass(AttributedStub::class);

        $a = $ac->attribute($attribute);
        $this->assertInstanceOf($attribute, $a);
        $this->assertSame('a', $a->name);

        $a = $ac->method('attributes')->attribute($attribute);
        $this->assertInstanceOf($attribute, $a);
        $this->assertSame('b', $a->name);
    }
}
