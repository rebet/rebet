<?php
namespace Rebet\Tests\Annotation;

use Rebet\Tests\RebetTestCase;
use Rebet\Annotation\ClassAnnotations;
use Rebet\Routing\Annotation\Surface;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;

class ClassAnnotationsTest extends RebetTestCase
{
    public function setUp()
    {
    }

    public function test_annotaion()
    {
        $ca = ClassAnnotations::of(ClassAnnotationsTest_Mock::class);
        $surface = $ca->annotation(Surface::class);
        $this->assertInstanceOf(Surface::class, $surface);
        $this->assertSame(['web'], $surface->allows);
    }
}

/**
 * @Surface("web")
 */
class ClassAnnotationsTest_Mock
{
}
