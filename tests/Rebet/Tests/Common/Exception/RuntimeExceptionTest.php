<?php
namespace Rebet\Tests\Common\Exception;

use Rebet\Common\Exception\RebetException;
use Rebet\Common\Exception\RuntimeException;
use Rebet\Tests\RebetTestCase;

class RuntimeExceptionTest extends RebetTestCase
{
    public function test___construct()
    {
        $e = new RuntimeException('test');
        $this->assertInstanceOf(RuntimeException::class, $e);
        $this->assertInstanceOf(RebetException::class, $e);
        $this->assertInstanceOf(\RuntimeException::class, $e);
    }
}
