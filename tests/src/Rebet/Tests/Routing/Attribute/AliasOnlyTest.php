<?php

declare(strict_types=1);

namespace Rebet\Tests\Routing\Attribute;

use Rebet\Attribute\AttributedClass;
use Rebet\Routing\Attribute\AliasOnly;
use Rebet\Tests\RebetTestCase;
use TestApp\Stub\AttributedStub;

class AliasOnlyTest extends RebetTestCase
{
    public function test_attribute(): void
    {
        $attribute = AliasOnly::class;
        $ac        = new AttributedClass(AttributedStub::class);

        $a = $ac->attribute($attribute);
        $this->assertInstanceOf($attribute, $a);

        $a = $ac->method('attributes')->attribute($attribute);
        $this->assertInstanceOf($attribute, $a);
    }
}
