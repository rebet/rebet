<?php
namespace Rebet\Tests\Tools\DateTime\Exception;

use Rebet\Tests\RebetTestCase;
use Rebet\Tools\DateTime\Exception\DateTimeFormatException;

class DateTimeFormatExceptionTest extends RebetTestCase
{
    public function test___construct()
    {
        $e = new DateTimeFormatException('test');
        $this->assertInstanceOf(DateTimeFormatException::class, $e);
    }
}
