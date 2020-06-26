<?php
namespace Rebet\Tests\Log\Driver;

use Rebet\Log\Driver\NullDriver;
use Rebet\Log\LogLevel;
use Rebet\Tests\RebetTestCase;

class NullDriverTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(NullDriver::class, new NullDriver());
    }

    public function test_log()
    {
        $driver = new NullDriver();
        $driver->log(LogLevel::DEBUG, 'Nothing happens.');
        $this->assertTrue(true);
    }
}
