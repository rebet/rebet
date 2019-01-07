<?php
namespace Rebet\Tests\Auth\Annotation;

use Rebet\Annotation\AnnotatedClass;
use Rebet\Auth\Annotation\Authenticator;
use Rebet\Tests\Mock\AnnotatedStub;
use Rebet\Tests\RebetTestCase;

class AuthenticatorTest extends RebetTestCase
{
    public function test_annotation()
    {
        $annotation = Authenticator::class;
        $ac         = new AnnotatedClass(AnnotatedStub::class);

        $a = $ac->annotation($annotation);
        $this->assertInstanceOf($annotation, $a);
        $this->assertSame('a', $a->name);

        $a = $ac->method('annotations')->annotation($annotation);
        $this->assertInstanceOf($annotation, $a);
        $this->assertSame('b', $a->name);
    }
}
