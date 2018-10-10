<?php
namespace Rebet\Tests\Annotation;

use Rebet\Tests\RebetTestCase;
use Rebet\Annotation\AnnotatedClass;
use Doctrine\Common\Annotations\AnnotationReader;
use Rebet\Routing\Annotation\Where;
use Rebet\Annotation\AnnotatedProperty;
use Rebet\Routing\Annotation\Surface;

class AnnotatedPropertyTest extends RebetTestCase
{
    public function test_construct()
    {
        $rp = new \ReflectionProperty(AnnotatedPropertyTest_Mock::class, 'foo');
        $ap = new AnnotatedProperty($rp);
        $this->assertInstanceOf(AnnotatedProperty::class, $ap);
    }
    
    public function test_of()
    {
        $rp = new \ReflectionProperty(AnnotatedPropertyTest_Mock::class, 'foo');
        $ap = AnnotatedProperty::of($rp);
        $this->assertInstanceOf(AnnotatedProperty::class, $ap);

        $ap = AnnotatedProperty::of('foo', AnnotatedPropertyTest_Mock::class);
        $this->assertInstanceOf(AnnotatedProperty::class, $ap);
        
        $mock = new AnnotatedPropertyTest_Mock();
        $ap = AnnotatedProperty::of('foo', $mock);
        $this->assertInstanceOf(AnnotatedProperty::class, $ap);
    }
    
    public function test_annotaion()
    {
        $ap = AnnotatedProperty::of('foo', AnnotatedPropertyTest_Mock::class);
        $annot = $ap->annotation(PropertyAnnot::class, false);
        $this->assertNull($annot);
        
        $annot = $ap->annotation(PropertyAnnot::class);
        $this->assertInstanceOf(PropertyAnnot::class, $annot);
        $this->assertSame('prop', $annot->value);

        $ap = AnnotatedProperty::of('bar', AnnotatedPropertyTest_Mock::class);
        $annot = $ap->annotation(PropertyAnnot::class);
        $this->assertInstanceOf(PropertyAnnot::class, $annot);
        $this->assertSame('bar-prop', $annot->value);
    }

    public function test_nonAnnotaion()
    {
        $ap = AnnotatedProperty::of('foo', AnnotatedPropertyTest_Mock::class);
        $this->assertNull($ap->annotation(Surface::class, false));
        $this->assertNull($ap->annotation(Surface::class));
    }
    
    public function test_annotaions()
    {
        $ap = AnnotatedProperty::of('bar', AnnotatedPropertyTest_Mock::class);
        $annotations = $ap->annotations();

        $annot = $annotations[0];
        $this->assertInstanceOf(PropertyAnnot::class, $annot);
        $this->assertSame('bar-prop', $annot->value);
    }
    
    public function test_declaringClass()
    {
        $ac = AnnotatedProperty::of('foo', AnnotatedPropertyTest_Mock::class)->declaringClass();
        $this->assertInstanceOf(AnnotatedClass::class, $ac);
        
        $annot = $ac->annotation(PropertyAnnot::class);
        $this->assertInstanceOf(PropertyAnnot::class, $annot);
        $this->assertSame('prop', $annot->value);
    }
}

/**
 * @PropertyAnnot("prop")
 */
class AnnotatedPropertyTest_Mock
{
    public $foo;

    /**
     * @PropertyAnnot("bar-prop")
     */
    public $bar;
}

/**
 * @Annotation
 * @Target({"CLASS","PROPERTY"})
 */
class PropertyAnnot
{
    public $value;
}
