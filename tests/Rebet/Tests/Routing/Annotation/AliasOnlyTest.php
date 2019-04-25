<?php
namespace Rebet\Tests\Routing\Annotation;

use Rebet\Annotation\AnnotatedClass;
use Rebet\Routing\Annotation\AliasOnly;
use Rebet\Tests\Mock\Stub\AnnotatedStub;
use Rebet\Tests\RebetTestCase;

class AliasOnlyTest extends RebetTestCase
{
    public function test_annotation()
    {
        $annotation = AliasOnly::class;
        $ac         = new AnnotatedClass(AnnotatedStub::class);

        $a = $ac->annotation($annotation);
        $this->assertInstanceOf($annotation, $a);

        $a = $ac->method('annotations')->annotation($annotation);
        $this->assertInstanceOf($annotation, $a);
    }
}
