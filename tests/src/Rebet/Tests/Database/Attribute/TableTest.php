<?php
namespace Rebet\Tests\Database\Attribute;

use Rebet\Attribute\AttributedClass;
use Rebet\Database\Attribute\Table;
use Rebet\Tests\RebetTestCase;
use TestApp\Stub\AttributedStub;

class TableTest extends RebetTestCase
{
    public function test_attribute()
    {
        $attribute = Table::class;
        $ac        = new AttributedClass(AttributedStub::class);

        $a = $ac->attribute($attribute);
        $this->assertInstanceOf($attribute, $a);
        $this->assertSame("table_name", $a->value);
    }
}
