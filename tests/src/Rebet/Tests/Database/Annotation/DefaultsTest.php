<?php
namespace Rebet\Tests\Database\Annotation;

use Rebet\Annotation\AnnotatedClass;
use Rebet\Database\Annotation\Defaults;
use Rebet\DateTime\DateTime;
use Rebet\Tests\Mock\Stub\AnnotatedStub;
use Rebet\Tests\RebetTestCase;

class DefaultsTest extends RebetTestCase
{
    public function test_annotation()
    {
        DateTime::setTestNow('2010-01-02 03:04:05');

        $annotation = Defaults::class;
        $ac         = new AnnotatedClass(AnnotatedStub::class);

        $a = $ac->property('annotations')->annotation($annotation);
        $this->assertInstanceOf($annotation, $a);
        $this->assertSame('now', $a->value);
    }
}
