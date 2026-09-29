<?php

declare(strict_types=1);

namespace Rebet\Tests\Attribute;

use Rebet\Attribute\AttributedClass;
use Rebet\Attribute\AttributedProperty;
use Rebet\Routing\Attribute\Channel;
use Rebet\Tests\RebetTestCase;

class AttributedPropertyTest extends RebetTestCase
{
    public function test_construct(): void
    {
        $rp = new \ReflectionProperty(AttributedPropertyTest_Mock::class, 'foo');
        $ap = new AttributedProperty($rp);
        $this->assertInstanceOf(AttributedProperty::class, $ap);
    }

    public function test_of(): void
    {
        $rp = new \ReflectionProperty(AttributedPropertyTest_Mock::class, 'foo');
        $ap = AttributedProperty::of($rp);
        $this->assertInstanceOf(AttributedProperty::class, $ap);

        $ap = AttributedProperty::of('foo', AttributedPropertyTest_Mock::class);
        $this->assertInstanceOf(AttributedProperty::class, $ap);

        $mock = new AttributedPropertyTest_Mock();
        $ap   = AttributedProperty::of('foo', $mock);
        $this->assertInstanceOf(AttributedProperty::class, $ap);
    }

    public function test_attribute(): void
    {
        $ap   = AttributedProperty::of('foo', AttributedPropertyTest_Mock::class);
        $attr = $ap->attribute(PropertyAttr::class, false);
        $this->assertNull($attr);

        $attr = $ap->attribute(PropertyAttr::class);
        $this->assertInstanceOf(PropertyAttr::class, $attr);
        $this->assertSame('prop', $attr->value);

        $ap   = AttributedProperty::of('bar', AttributedPropertyTest_Mock::class);
        $attr = $ap->attribute(PropertyAttr::class);
        $this->assertInstanceOf(PropertyAttr::class, $attr);
        $this->assertSame('bar-prop', $attr->value);
    }

    public function test_nonAttribute(): void
    {
        $ap = AttributedProperty::of('foo', AttributedPropertyTest_Mock::class);
        $this->assertNull($ap->attribute(Channel::class, false));
        $this->assertNull($ap->attribute(Channel::class));
    }

    public function test_attributes(): void
    {
        $ap         = AttributedProperty::of('bar', AttributedPropertyTest_Mock::class);
        $attributes = $ap->attributes();

        $attr = $attributes[0];
        $this->assertInstanceOf(PropertyAttr::class, $attr);
        $this->assertSame('bar-prop', $attr->value);
    }

    public function test_declaringClass(): void
    {
        $ac = AttributedProperty::of('foo', AttributedPropertyTest_Mock::class)->declaringClass();
        $this->assertInstanceOf(AttributedClass::class, $ac);

        $attr = $ac->attribute(PropertyAttr::class);
        $this->assertInstanceOf(PropertyAttr::class, $attr);
        $this->assertSame('prop', $attr->value);
    }

    public function test_reflector(): void
    {
        $ap = AttributedProperty::of('foo', AttributedPropertyTest_Mock::class);
        $this->assertInstanceOf(\ReflectionProperty::class, $ap->reflector());
    }
}

#[PropertyAttr("prop")]
class AttributedPropertyTest_Mock
{
    public $foo;

    #[PropertyAttr("bar-prop")]
    public $bar;
}

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_PROPERTY)]
class PropertyAttr
{
    public $value;

    public function __construct($value = null)
    {
        $this->value = $value;
    }
}
