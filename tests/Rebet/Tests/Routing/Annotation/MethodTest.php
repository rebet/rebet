<?php
namespace Rebet\Tests\Routing\Annotation;

use Rebet\Annotation\AnnotatedClass;
use Rebet\Routing\Annotation\Method;
use Rebet\Tests\Mock\Stub\AnnotatedStub;
use Rebet\Tests\RebetTestCase;

class MethodTest extends RebetTestCase
{
    public function test_annotation()
    {
        $annotation = Method::class;
        $ac         = new AnnotatedClass(AnnotatedStub::class);

        $a = $ac->annotation($annotation);
        $this->assertInstanceOf($annotation, $a);
        $this->assertSame(['GET', 'PUT'], $a->allows);
        $this->assertTrue($a->allow('GET'));
        $this->assertTrue($a->allow('PUT'));
        $this->assertFalse($a->allow('HEAD'));
        $this->assertFalse($a->allow('OPTION'));

        $a = $ac->method('annotations')->annotation($annotation);
        $this->assertInstanceOf($annotation, $a);
        $this->assertSame(['HEAD', 'OPTION'], $a->rejects);
        $this->assertFalse($a->reject('GET'));
        $this->assertFalse($a->reject('PUT'));
        $this->assertTrue($a->reject('HEAD'));
        $this->assertTrue($a->reject('OPTION'));
    }
}
