<?php
namespace Rebet\Tests\Common\Annotation;

use Rebet\Annotation\AnnotatedClass;
use Rebet\Common\Annotation\Nest;
use Rebet\Tests\Common\Mock\User;
use Rebet\Tests\Mock\Stub\AnnotatedStub;
use Rebet\Tests\RebetTestCase;

class NestTest extends RebetTestCase
{
    public function test_annotation()
    {
        $annotation = Nest::class;
        $ac         = new AnnotatedClass(AnnotatedStub::class);

        $a = $ac->property('annotations')->annotation($annotation);
        $this->assertInstanceOf($annotation, $a);
        $this->assertSame(User::class, $a->value);
    }
}
