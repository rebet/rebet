<?php
namespace Rebet\Tests\Routing\Attribute;

use App\Stub\AttributedStub;
use Rebet\Attribute\AttributedClass;
use Rebet\Routing\Attribute\Channel;
use Rebet\Tests\RebetTestCase;

class ChannelTest extends RebetTestCase
{
    public function test_attribute()
    {
        $attribute = Channel::class;
        $ac        = new AttributedClass(AttributedStub::class);

        $a = $ac->attribute($attribute);
        $this->assertInstanceOf($attribute, $a);
        $this->assertSame(['web'], $a->allows);
        $this->assertTrue($a->allow('web'));
        $this->assertFalse($a->allow('api'));
        $this->assertFalse($a->reject('web'));
        $this->assertTrue($a->reject('api'));

        // Original Annotation test used @Channel(rejects={"web", "api"}) on the method,
        // so here the same values are inverted into allows({"web", "api"}) and verified.
        $a = $ac->method('attributes')->attribute($attribute);
        $this->assertInstanceOf($attribute, $a);
        $this->assertSame(['web', 'api'], $a->allows);
        $this->assertTrue($a->allow('web'));
        $this->assertTrue($a->allow('api'));
        $this->assertFalse($a->allow('cli'));
        $this->assertFalse($a->reject('web'));
        $this->assertFalse($a->reject('api'));
        $this->assertTrue($a->reject('cli'));
    }

    public function test_allow_empty()
    {
        $a = new Channel();
        $this->assertSame([], $a->allows);
        $this->assertTrue($a->allow('web'));
        $this->assertTrue($a->allow('anything'));
        $this->assertFalse($a->reject('web'));
    }
}
