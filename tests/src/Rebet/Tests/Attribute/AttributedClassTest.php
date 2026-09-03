<?php
namespace Rebet\Tests\Attribute;

use Rebet\Attribute\AttributedClass;
use Rebet\Attribute\AttributedMethod;
use Rebet\Attribute\AttributedProperty;
use Rebet\Routing\Attribute\Channel;
use Rebet\Routing\Attribute\Method;
use Rebet\Routing\Attribute\Where;
use Rebet\Tests\RebetTestCase;

class AttributedClassTest extends RebetTestCase
{
    public function test_construct()
    {
        $ac = new AttributedClass(AttributedClassTest_Mock::class);
        $this->assertInstanceOf(AttributedClass::class, $ac);

        $mock = new AttributedClassTest_Mock();
        $ac   = new AttributedClass($mock);
        $this->assertInstanceOf(AttributedClass::class, $ac);
    }

    public function test_of()
    {
        $ac = AttributedClass::of(AttributedClassTest_Mock::class);
        $this->assertInstanceOf(AttributedClass::class, $ac);

        $mock = new AttributedClassTest_Mock();
        $ac   = AttributedClass::of($mock);
        $this->assertInstanceOf(AttributedClass::class, $ac);
    }

    public function test_attribute()
    {
        $ac      = AttributedClass::of(AttributedClassTest_Mock::class);
        $channel = $ac->attribute(Channel::class);
        $this->assertInstanceOf(Channel::class, $channel);
        $this->assertSame(['web'], $channel->allows);

        $where = $ac->attribute(Where::class);
        $this->assertInstanceOf(Where::class, $where);
        $this->assertSame(['id' => '[0-9]+', 'code' => '[a-zA-Z]+'], $where->wheres);
    }

    public function test_nonAttribute()
    {
        $ac = AttributedClass::of(AttributedClassTest_Mock::class);
        $this->assertNull($ac->attribute(Method::class));
    }

    public function test_attributes()
    {
        $ac         = AttributedClass::of(AttributedClassTest_Mock::class);
        $attributes = $ac->attributes();

        $channel = $attributes[0];
        $this->assertInstanceOf(Channel::class, $channel);
        $this->assertSame(['web'], $channel->allows);

        $where = $attributes[1];
        $this->assertInstanceOf(Where::class, $where);
        $this->assertSame(['id' => '[0-9]+', 'code' => '[a-zA-Z]+'], $where->wheres);
    }

    public function test_method()
    {
        $am = AttributedClass::of(AttributedClassTest_Mock::class)->method('bar');
        $this->assertInstanceOf(AttributedMethod::class, $am);
    }

    public function test_property()
    {
        $ap = AttributedClass::of(AttributedClassTest_Mock::class)->property('foo');
        $this->assertInstanceOf(AttributedProperty::class, $ap);
    }

    public function test_properties()
    {
        $aps = AttributedClass::of(AttributedClassTest_Mock::class)->properties();
        $this->assertCount(1, $aps);
        $this->assertInstanceOf(AttributedProperty::class, $aps[0]);
        $this->assertSame('foo', $aps[0]->reflector()->getName());
    }

    public function test_reflector()
    {
        $ac = AttributedClass::of(AttributedClassTest_Mock::class);
        $this->assertInstanceOf(\ReflectionClass::class, $ac->reflector());
    }

    public function test_subClass()
    {
        $ac = AttributedClass::of(AttributedClassTest_Mock_Sub::class);

        $am = $ac->method('bar');
        $this->assertInstanceOf(AttributedMethod::class, $am);
        $this->assertSame('bar', $am->reflector()->getName());
        $this->assertSame(AttributedClassTest_Mock::class, $am->reflector()->getDeclaringClass()->getName());
        $channel = $am->attribute(Channel::class);
        $this->assertInstanceOf(Channel::class, $channel);
        $this->assertSame(['web'], $channel->allows);

        $ap = $ac->property('foo');
        $this->assertInstanceOf(AttributedProperty::class, $ap);
        $this->assertSame('foo', $ap->reflector()->getName());
        $this->assertSame(AttributedClassTest_Mock::class, $ap->reflector()->getDeclaringClass()->getName());

        $aps = $ac->properties();
        $this->assertCount(1, $aps);
        $this->assertSame('foo', $aps[0]->reflector()->getName());

        $attributes = $ac->attributes();
        $this->assertEmpty($attributes);
    }
}

#[Channel("web")]
#[Where(id: "[0-9]+", code: "[a-zA-Z]+")]
class AttributedClassTest_Mock
{
    public $foo;

    #[Channel("web")]
    public function bar()
    {
    }
}

class AttributedClassTest_Mock_Sub extends AttributedClassTest_Mock
{
}
