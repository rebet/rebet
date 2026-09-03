<?php
namespace Rebet\Tests\Routing\Attribute;

use App\Stub\AttributedStub;
use Rebet\Attribute\AttributedClass;
use Rebet\Routing\Attribute\Method;
use Rebet\Tests\RebetTestCase;

class MethodTest extends RebetTestCase
{
    public function test_attribute()
    {
        $attribute = Method::class;
        $ac        = new AttributedClass(AttributedStub::class);

        $a = $ac->attribute($attribute);
        $this->assertInstanceOf($attribute, $a);
        $this->assertSame(['GET', 'PUT'], $a->allows);
        $this->assertTrue($a->allow('GET'));
        $this->assertTrue($a->allow('PUT'));
        $this->assertFalse($a->allow('HEAD'));
        $this->assertFalse($a->allow('OPTION'));

        // Original Annotation test used @Method(rejects={"HEAD", "OPTION"}) on the method,
        // so here the same values are inverted into allows({"HEAD", "OPTION"}) and verified.
        $a = $ac->method('attributes')->attribute($attribute);
        $this->assertInstanceOf($attribute, $a);
        $this->assertSame(['HEAD', 'OPTION'], $a->allows);
        $this->assertFalse($a->allow('GET'));
        $this->assertFalse($a->allow('PUT'));
        $this->assertTrue($a->allow('HEAD'));
        $this->assertTrue($a->allow('OPTION'));
        $this->assertTrue($a->reject('GET'));
        $this->assertTrue($a->reject('PUT'));
        $this->assertFalse($a->reject('HEAD'));
        $this->assertFalse($a->reject('OPTION'));
    }

    public function test_allow_empty()
    {
        $a = new Method();
        $this->assertSame([], $a->allows);
        $this->assertTrue($a->allow('GET'));
        $this->assertTrue($a->allow('anything'));
        $this->assertFalse($a->reject('GET'));
    }

    public function test_allow_caseInsensitive()
    {
        $a = new Method('GET');
        $this->assertTrue($a->allow('get'));
        $this->assertTrue($a->allow('Get'));
    }
}
