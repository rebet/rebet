<?php
namespace Rebet\Tests\DateTime\Exception;

use Rebet\DateTime\Exception\DateTimeFormatException;
use Rebet\Tests\RebetTestCase;

class DateTimeFormatExceptionTest extends RebetTestCase
{
    public function test___construct()
    {
        $e = new DateTimeFormatException('test');
        $this->assertInstanceOf(DateTimeFormatException::class, $e);
    }
}
