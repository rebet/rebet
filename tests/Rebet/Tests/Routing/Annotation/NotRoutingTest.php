<?php
namespace Rebet\Tests\Routing\Annotation;

use Rebet\Annotation\AnnotatedClass;
use Rebet\Routing\Annotation\NotRouting;
use Rebet\Tests\Mock\Stub\AnnotatedStub;
use Rebet\Tests\RebetTestCase;

class NotRoutingTest extends RebetTestCase
{
    public function test_annotation()
    {
        $annotation = NotRouting::class;
        $ac         = new AnnotatedClass(AnnotatedStub::class);

        $a = $ac->method('annotations')->annotation($annotation);
        $this->assertInstanceOf($annotation, $a);
    }
}
