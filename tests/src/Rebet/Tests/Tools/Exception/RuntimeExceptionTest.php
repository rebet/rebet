<?php
namespace Rebet\Tests\Tools\Exception;

use Rebet\Tools\Exception\RebetException;
use Rebet\Tools\Exception\RuntimeException;
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
