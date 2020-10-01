<?php
namespace Rebet\Tests\Database\Annotation;

use Rebet\Annotation\AnnotatedClass;
use Rebet\Database\Annotation\PhpType;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tests\Mock\Stub\AnnotatedStub;
use Rebet\Tests\RebetTestCase;

class PhpTypeTest extends RebetTestCase
{
    public function test_annotation()
    {
        $annotation = PhpType::class;
        $ac         = new AnnotatedClass(AnnotatedStub::class);

        $a = $ac->property('annotations')->annotation($annotation);
        $this->assertInstanceOf($annotation, $a);
        $this->assertSame(DateTime::class, $a->value);
    }
}
