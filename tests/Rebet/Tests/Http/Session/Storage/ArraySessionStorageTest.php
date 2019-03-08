<?php
namespace Rebet\Tests\Http\Session\Storage;

use Rebet\Http\Session\Storage\ArraySessionStorage;
use Rebet\Tests\RebetTestCase;

class ArraySessionStorageTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(ArraySessionStorage::class, new ArraySessionStorage());
    }
}
