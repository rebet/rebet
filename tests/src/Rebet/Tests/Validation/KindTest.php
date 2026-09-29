<?php

declare(strict_types=1);

namespace Rebet\Tests\Validation;

use Rebet\Tests\RebetTestCase;
use Rebet\Validation\Kind;

class KindTest extends RebetTestCase
{
    public function test_translatable(): void
    {
        $this->assertSame('TYPE_CONSISTENCY_CHECK', Kind::TYPE_CONSISTENCY_CHECK()->translate('label', 'ja'));
    }
}
