<?php
namespace Rebet\Tests\Database\Attribute;

use App\Stub\AttributedStub;
use Rebet\Attribute\AttributedClass;
use Rebet\Database\Attribute\Defaults;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\DateTime\DateTime;

class DefaultsTest extends RebetTestCase
{
    public function test_attribute()
    {
        DateTime::setTestNow('2010-01-02 03:04:05');

        $attribute = Defaults::class;
        $ac        = new AttributedClass(AttributedStub::class);

        $a = $ac->property('attributes')->attribute($attribute);
        $this->assertInstanceOf($attribute, $a);
        $this->assertSame('now', $a->value);
    }
}
