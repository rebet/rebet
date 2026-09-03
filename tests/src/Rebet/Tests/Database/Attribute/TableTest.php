<?php
namespace Rebet\Tests\Database\Attribute;

use App\Stub\AttributedStub;
use Rebet\Attribute\AttributedClass;
use Rebet\Database\Attribute\Table;
use Rebet\Tests\RebetTestCase;

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
