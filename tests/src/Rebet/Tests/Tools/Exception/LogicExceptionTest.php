<?php
namespace Rebet\Tests\Tools\Exception;

use Rebet\Tools\Exception\LogicException;
use Rebet\Tools\Exception\RebetException;
use Rebet\Tests\RebetTestCase;

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
