<?php
namespace Rebet\Tests\Routing\Annotation;

use App\Stub\AnnotatedStub;
use Rebet\Annotation\AnnotatedClass;
use Rebet\Routing\Annotation\Where;
use Rebet\Tests\RebetTestCase;

class WhereTest extends RebetTestCase
{
    public function test_annotation()
    {
        $annotation = Where::class;
        $ac         = new AnnotatedClass(AnnotatedStub::class);

        $a = $ac->annotation($annotation);
        $this->assertInstanceOf($annotation, $a);
        $this->assertSame(["id" => "[0-9]+"], $a->wheres);

        $a = $ac->method('annotations')->annotation($annotation);
        $this->assertInstanceOf($annotation, $a);
        $this->assertSame(["seq" => "[0-9]+", "code" => "[a-zA-Z]+"], $a->wheres);
    }
}
