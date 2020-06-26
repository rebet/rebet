<?php
namespace Rebet\Tests\Database\Annotation;

use Rebet\Annotation\AnnotatedClass;
use Rebet\Database\Annotation\PrimaryKey;
use Rebet\Tests\Mock\Stub\AnnotatedStub;
use Rebet\Tests\RebetTestCase;

class PrimaryKeyTest extends RebetTestCase
{
    public function test_annotation()
    {
        $annotation = PrimaryKey::class;
        $ac         = new AnnotatedClass(AnnotatedStub::class);

        $a = $ac->property('annotations')->annotation($annotation);
        $this->assertInstanceOf($annotation, $a);
    }
}
