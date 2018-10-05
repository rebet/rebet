<?php
namespace Rebet\Tests\Annotation;

use Rebet\Tests\RebetTestCase;
use Rebet\Annotation\AnnotatedClass;
use Rebet\Routing\Annotation\Surface;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;

class AnnotatedClassTest extends RebetTestCase
{
    public function setUp()
    {
    }

    public function test_annotaion()
    {
        $ac = AnnotatedClass::of(AnnotatedClassTest_Mock::class);
        $surface = $ac->annotation(Surface::class);
        $this->assertInstanceOf(Surface::class, $surface);
        $this->assertSame(['web'], $surface->allows);
    }
}

/**
 * @Surface("web")
 */
class AnnotatedClassTest_Mock
{
}
