<?php
namespace Rebet\Tests\Tools\Exception;

use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Exception\LogicException;
use Rebet\Tools\Exception\RebetException;

class LogicExceptionTest extends RebetTestCase
{
    public function test___construct()
    {
        $e = new LogicException('test');
        $this->assertInstanceOf(LogicException::class, $e);
        $this->assertInstanceOf(RebetException::class, $e);
        $this->assertInstanceOf(\LogicException::class, $e);
    }
}
