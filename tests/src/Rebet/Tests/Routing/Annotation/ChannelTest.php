<?php
namespace Rebet\Tests\Routing\Annotation;

use App\Stub\AnnotatedStub;
use Rebet\Annotation\AnnotatedClass;
use Rebet\Routing\Annotation\Channel;
use Rebet\Tests\RebetTestCase;

class ChannelTest extends RebetTestCase
{
    public function test_annotation()
    {
        $annotation = Channel::class;
        $ac         = new AnnotatedClass(AnnotatedStub::class);

        $a = $ac->annotation($annotation);
        $this->assertInstanceOf($annotation, $a);
        $this->assertSame(['web'], $a->allows);

        $a = $ac->method('annotations')->annotation($annotation);
        $this->assertSame(['web', 'api'], $a->rejects);
    }
}
