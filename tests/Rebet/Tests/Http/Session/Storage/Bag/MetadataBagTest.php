<?php
namespace Rebet\Tests\Http\Session\Storage\Bag;

use Rebet\Http\Session\Storage\Bag\MetadataBag;
use Rebet\Tests\RebetTestCase;

class MetadataBagTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(MetadataBag::class, new MetadataBag('test'));
    }
}
