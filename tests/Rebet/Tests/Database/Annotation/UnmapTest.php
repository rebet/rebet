<?php
namespace Rebet\Tests\Database\Annotation;

use Rebet\Annotation\AnnotatedClass;
use Rebet\Database\Annotation\Unmap;
use Rebet\Tests\Mock\Stub\AnnotatedStub;
use Rebet\Tests\RebetTestCase;

class UnmapTest extends RebetTestCase
{
    public function test_annotation()
    {
        $annotation = Unmap::class;
        $ac         = new AnnotatedClass(AnnotatedStub::class);

        $a = $ac->property('annotations')->annotation($annotation);
        $this->assertInstanceOf($annotation, $a);
    }
}
