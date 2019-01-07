<?php
namespace Rebet\Tests\Auth\Annotation;

use Rebet\Annotation\AnnotatedClass;
use Rebet\Auth\Annotation\Role;
use Rebet\Tests\Mock\AnnotatedStub;
use Rebet\Tests\RebetTestCase;

class RoleTest extends RebetTestCase
{
    public function test_annotation()
    {
        $annotation = Role::class;
        $ac         = new AnnotatedClass(AnnotatedStub::class);

        $a = $ac->annotation($annotation);
        $this->assertInstanceOf($annotation, $a);
        $this->assertSame(['a'], $a->names);

        $a = $ac->method('annotations')->annotation($annotation);
        $this->assertInstanceOf($annotation, $a);
        $this->assertSame(['b', 'c'], $a->names);
    }
}
