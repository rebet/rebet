<?php
namespace Rebet\Tests\Annotation;

use Rebet\Tests\RebetTestCase;
use Rebet\Annotation\ClassAnnotations;
use Rebet\Routing\Annotation\Surface;
use Doctrine\Common\Annotations\AnnotationReader;

class ClassAnnotationsTest extends RebetTestCase
{
    public $reader;
    
    public function setUp()
    {
        $this->reader = new AnnotationReader();
    }

    public function test_annotaion()
    {
        // $ca = new ClassAnnotations($this->reader, ClassAnnotationsTest_Mock::class);
        // $surface = $ca->annotation(Surface::class);
        // $this->assertInstanceOf(Surface::class, $surface);
        // $this->assertSame(['web'], $surface->allows);
    }
}

/**
 * @Surface("web")
 */
class ClassAnnotationsTest_Mock
{
}
