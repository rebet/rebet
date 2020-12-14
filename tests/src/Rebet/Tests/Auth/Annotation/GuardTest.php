<?php
namespace Rebet\Tests\Auth\Annotation;

use Rebet\Annotation\AnnotatedClass;
use Rebet\Auth\Annotation\Guard;
use Rebet\Tests\Mock\Stub\AnnotatedStub;
use Rebet\Tests\RebetTestCase;

class GuardTest extends RebetTestCase
{
    public function test_annotation()
    {
        $annotation = Guard::class;
        $ac         = new AnnotatedClass(AnnotatedStub::class);

        $a = $ac->annotation($annotation);
        $this->assertInstanceOf($annotation, $a);
        $this->assertSame('a', $a->name);

        $a = $ac->method('annotations')->annotation($annotation);
        $this->assertInstanceOf($annotation, $a);
        $this->assertSame('b', $a->name);
    }
}
