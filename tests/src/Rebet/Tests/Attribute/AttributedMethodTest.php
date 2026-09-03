<?php
namespace Rebet\Tests\Attribute;

use Rebet\Attribute\AttributedClass;
use Rebet\Attribute\AttributedMethod;
use Rebet\Routing\Attribute\Channel;
use Rebet\Routing\Attribute\Method;
use Rebet\Routing\Attribute\Where;
use Rebet\Tests\RebetTestCase;

class AttributedMethodTest extends RebetTestCase
{
    public function test_construct()
    {
        $rm = new \ReflectionMethod(AttributedMethodTest_Mock::class, 'foo');
        $am = new AttributedMethod($rm);
        $this->assertInstanceOf(AttributedMethod::class, $am);
    }

    public function test_of()
    {
        $rm = new \ReflectionMethod(AttributedMethodTest_Mock::class, 'foo');
        $am = AttributedMethod::of($rm);
        $this->assertInstanceOf(AttributedMethod::class, $am);

        $am = AttributedMethod::of('foo', AttributedMethodTest_Mock::class);
        $this->assertInstanceOf(AttributedMethod::class, $am);

        $mock = new AttributedMethodTest_Mock();
        $am   = AttributedMethod::of('foo', $mock);
        $this->assertInstanceOf(AttributedMethod::class, $am);
    }

    public function test_attribute()
    {
        $am      = AttributedMethod::of('foo', AttributedMethodTest_Mock::class);
        $channel = $am->attribute(Channel::class, false);
        $this->assertNull($channel);

        $channel = $am->attribute(Channel::class);
        $this->assertInstanceOf(Channel::class, $channel);
        $this->assertSame(['web'], $channel->allows);

        $where = $am->attribute(Where::class);
        $this->assertInstanceOf(Where::class, $where);
        $this->assertSame(['id' => '[0-9]+'], $where->wheres);

        $am      = AttributedMethod::of('bar', AttributedMethodTest_Mock::class);
        $channel = $am->attribute(Channel::class);
        $this->assertInstanceOf(Channel::class, $channel);
        $this->assertSame(['api'], $channel->allows);

        $where = $am->attribute(Where::class);
        $this->assertNull($where);
    }

    public function test_nonAttribute()
    {
        $am = AttributedMethod::of('foo', AttributedMethodTest_Mock::class);
        $this->assertNull($am->attribute(Method::class, false));
        $this->assertNull($am->attribute(Method::class));
    }

    public function test_attributes()
    {
        $am         = AttributedMethod::of('bar', AttributedMethodTest_Mock::class);
        $attributes = $am->attributes();

        $channel = $attributes[0];
        $this->assertInstanceOf(Channel::class, $channel);
        $this->assertSame(['api'], $channel->allows);
    }

    public function test_declaringClass()
    {
        $ac = AttributedMethod::of('foo', AttributedMethodTest_Mock::class)->declaringClass();
        $this->assertInstanceOf(AttributedClass::class, $ac);

        $channel = $ac->attribute(Channel::class);
        $this->assertInstanceOf(Channel::class, $channel);
        $this->assertSame(['web'], $channel->allows);
    }

    public function test_reflector()
    {
        $am = AttributedMethod::of('foo', AttributedMethodTest_Mock::class);
        $this->assertInstanceOf(\ReflectionMethod::class, $am->reflector());
    }
}

#[Channel("web")]
class AttributedMethodTest_Mock
{
    #[Where(id: "[0-9]+")]
    public function foo($id)
    {
    }

    #[Channel("api")]
    public function bar()
    {
    }
}
