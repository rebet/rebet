<?php
namespace Rebet\Tests\Annotation;

use Rebet\Tests\RebetTestCase;
use Rebet\Annotation\AnnotatedClass;
use Rebet\Routing\Annotation\Surface;
use Doctrine\Common\Annotations\AnnotationReader;
use Rebet\Routing\Annotation\Where;
use Rebet\Annotation\AnnotatedMethod;
use Rebet\Annotation\AnnotatedProperty;
use Rebet\Routing\Annotation\Method;

class AnnotatedMethodTest extends RebetTestCase
{
    public function test_construct()
    {
        $rm = new \ReflectionMethod(AnnotatedMethodTest_Mock::class, 'foo');
        $am = new AnnotatedMethod($rm);
        $this->assertInstanceOf(AnnotatedMethod::class, $am);
    }
    
    public function test_of()
    {
        $rm = new \ReflectionMethod(AnnotatedMethodTest_Mock::class, 'foo');
        $am = AnnotatedMethod::of($rm);
        $this->assertInstanceOf(AnnotatedMethod::class, $am);

        $am = AnnotatedMethod::of('foo', AnnotatedMethodTest_Mock::class);
        $this->assertInstanceOf(AnnotatedMethod::class, $am);
        
        $mock = new AnnotatedMethodTest_Mock();
        $am = AnnotatedMethod::of('foo', $mock);
        $this->assertInstanceOf(AnnotatedMethod::class, $am);
    }
    
    public function test_annotaion()
    {
        $am = AnnotatedMethod::of('foo', AnnotatedMethodTest_Mock::class);
        $surface = $am->annotation(Surface::class);
        $this->assertNull($surface);
        
        $surface = $am->annotation(Surface::class, true);
        $this->assertInstanceOf(Surface::class, $surface);
        $this->assertSame(['web'], $surface->allows);

        $where = $am->annotation(Where::class);
        $this->assertInstanceOf(Where::class, $where);
        $this->assertSame(['id' => '[0-9]+'], $where->wheres);

        $am = AnnotatedMethod::of('bar', AnnotatedMethodTest_Mock::class);
        $surface = $am->annotation(Surface::class, true);
        $this->assertInstanceOf(Surface::class, $surface);
        $this->assertSame(['api'], $surface->allows);

        $where = $am->annotation(Where::class);
        $this->assertNull($where);
    }

    public function test_nonAnnotaion()
    {
        $am = AnnotatedMethod::of('foo', AnnotatedMethodTest_Mock::class);
        $this->assertNull($am->annotation(Method::class));
        $this->assertNull($am->annotation(Method::class, true));
    }
    
    public function test_annotaions()
    {
        $am = AnnotatedMethod::of('bar', AnnotatedMethodTest_Mock::class);
        $annotations = $am->annotations();

        $surface = $annotations[0];
        $this->assertInstanceOf(Surface::class, $surface);
        $this->assertSame(['api'], $surface->allows);
    }
    
    public function test_declaringClass()
    {
        $ac = AnnotatedMethod::of('foo', AnnotatedMethodTest_Mock::class)->declaringClass();
        $this->assertInstanceOf(AnnotatedClass::class, $ac);
        
        $surface = $ac->annotation(Surface::class);
        $this->assertInstanceOf(Surface::class, $surface);
        $this->assertSame(['web'], $surface->allows);
    }
}

/**
 * @Surface("web")
 */
class AnnotatedMethodTest_Mock
{
    /**
     * @Where({"id": "[0-9]+"})
     */
    public function foo($id)
    {
    }

    /**
     * @Surface("api")
     */
    public function bar()
    {
    }
}
