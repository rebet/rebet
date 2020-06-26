<?php
namespace Rebet\Tests\Common\Exception;

use Rebet\Common\Exception\LogicException;
use Rebet\Common\Exception\RebetException;
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
