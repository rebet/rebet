<?php
namespace Rebet\Tests\Auth\Attribute;

use App\Stub\AttributedStub;
use Rebet\Attribute\AttributedClass;
use Rebet\Auth\Attribute\Role;
use Rebet\Tests\RebetTestCase;

class RoleTest extends RebetTestCase
{
    public function test_attribute()
    {
        $attribute = Role::class;
        $ac        = new AttributedClass(AttributedStub::class);

        $a = $ac->attribute($attribute);
        $this->assertInstanceOf($attribute, $a);
        $this->assertSame(['a'], $a->names);

        $a = $ac->method('attributes')->attribute($attribute);
        $this->assertInstanceOf($attribute, $a);
        $this->assertSame(['b', 'c'], $a->names);
    }
}
