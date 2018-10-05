<?php
namespace Rebet\Tests\Annotation;

use Rebet\Tests\RebetTestCase;
use Rebet\Annotation\AnnotatedClass;
use Rebet\Routing\Annotation\Surface;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Rebet\Routing\Annotation\Where;

class AnnotatedClassTest extends RebetTestCase
{
    public function test_of()
    {
        $ac = AnnotatedClass::of(AnnotatedClassTest_Mock::class);
        $this->assertInstanceOf(AnnotatedClass::class, $ac);
    }
    
    public function test_annotaion()
    {
        $ac = AnnotatedClass::of(AnnotatedClassTest_Mock::class);
        $surface = $ac->annotation(Surface::class);
        $this->assertInstanceOf(Surface::class, $surface);
        $this->assertSame(['web'], $surface->allows);
        
        $where = $ac->annotation(Where::class);
        $this->assertInstanceOf(Where::class, $where);
        $this->assertSame(['id' => '[0-9]+', 'code' => '[a-zA-Z]+'], $where->wheres);
    }
    
    public function test_annotaions()
    {
        $ac = AnnotatedClass::of(AnnotatedClassTest_Mock::class);
        $annotations = $ac->annotations();

        $surface = $annotations[0];
        $this->assertInstanceOf(Surface::class, $surface);
        $this->assertSame(['web'], $surface->allows);

        $where = $annotations[1];
        $this->assertInstanceOf(Where::class, $where);
        $this->assertSame(['id' => '[0-9]+', 'code' => '[a-zA-Z]+'], $where->wheres);
    }
}

/**
 * @Surface("web")
 * @Where({"id": "[0-9]+", "code": "[a-zA-Z]+"})
 */
class AnnotatedClassTest_Mock
{
}
