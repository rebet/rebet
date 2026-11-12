<?php

declare(strict_types=1);

namespace Rebet\Tests\Validation;

use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Enum\Enumeration;
use Rebet\Tools\Reflection\Reflector;
use Rebet\Validation\Kind;

class KindTest extends RebetTestCase
{
    public function test_enum(): void
    {
        $this->assertInstanceOf(\BackedEnum::class, Kind::TypeConsistencyCheck);
        $this->assertInstanceOf(Enumeration::class, Kind::TypeConsistencyCheck);
        $this->assertSame([1, 2, 3], Kind::values());
        $this->assertSame(['TypeConsistencyCheck', 'TypeDependentCheck', 'Independently'], Kind::names());
        $this->assertSame(Kind::TypeDependentCheck, Kind::from(2));
        $this->assertSame(Kind::Independently, Kind::valueOf(3));
        $this->assertSame(Kind::Independently, Kind::nameOf('Independently'));
        $this->assertNull(Kind::valueOf(4));
        $this->assertSame(Kind::TypeConsistencyCheck, Reflector::convert('1', Kind::class));
    }

    public function test_translatable(): void
    {
        // Kind has no Translatable attribute, so that the label is not translated even if the dictionary has it.
        // (The dictionary of the group 'enum' has the labels of Kind in the test resource.)
        $this->assertSame('TypeConsistencyCheck', Kind::TypeConsistencyCheck->label());
        $this->assertSame('TypeConsistencyCheck', Kind::TypeConsistencyCheck->label('ja'));
        $this->assertSame('TypeConsistencyCheck', Kind::TypeConsistencyCheck->translate('label', 'ja'));
        $this->assertSame(['TypeConsistencyCheck', 'TypeDependentCheck', 'Independently'], Kind::labels(null, 'ja'));
    }
}
