<?php
namespace Rebet\Tests\Database\Annotation;

use App\Stub\AnnotatedStub;
use Rebet\Annotation\AnnotatedClass;
use Rebet\Database\Annotation\Table;
use Rebet\Tests\RebetTestCase;

class TableTest extends RebetTestCase
{
    public function test_annotation()
    {
        $annotation = Table::class;
        $ac         = new AnnotatedClass(AnnotatedStub::class);

        $a = $ac->annotation($annotation);
        $this->assertInstanceOf($annotation, $a);
        $this->assertSame("table_name", $a->value);
    }
}
