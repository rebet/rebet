<?php
namespace Rebet\Tests\Database\Driver;

use Rebet\Database\Driver\PdoDriver;
use Rebet\Tests\RebetTestCase;

class PdoDriverTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(PdoDriver::class, new PdoDriver('sqlite::memory:'));
    }
}
