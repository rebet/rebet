<?php

declare(strict_types=1);

namespace Rebet\Tests\Tools\DateTime\Exception;

use Rebet\Tests\RebetTestCase;
use Rebet\Tools\DateTime\Exception\DateTimeFormatException;

class DateTimeFormatExceptionTest extends RebetTestCase
{
    public function test___construct(): void
    {
        $e = new DateTimeFormatException('test');
        $this->assertInstanceOf(DateTimeFormatException::class, $e);
    }
}
