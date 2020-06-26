<?php
namespace Rebet\Tests\Config\Exception;

use Rebet\Config\Exception\ConfigNotDefineException;
use Rebet\Tests\RebetTestCase;

class ConfigNotDefineExceptionTest extends RebetTestCase
{
    public function test___construct()
    {
        $e = new ConfigNotDefineException('test');
        $this->assertInstanceOf(ConfigNotDefineException::class, $e);
    }
}
