<?php
namespace Rebet\Tests\Validation;

use Rebet\Tests\RebetTestCase;
use Rebet\Validation\Kind;

class KindTest extends RebetTestCase
{
    public function test_translatable()
    {
        $this->assertSame('TYPE_CONSISTENCY_CHECK', Kind::TYPE_CONSISTENCY_CHECK()->translate('label', 'ja'));
    }
}
