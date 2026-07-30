<?php
namespace Rebet\Tests\Tools\Config\Exception;

use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Config\Exception\ConfigNotDefineException;

class ConfigNotDefineExceptionTest extends RebetTestCase
{
    public function test___construct()
    {
        $e = new ConfigNotDefineException('test');
        $this->assertInstanceOf(ConfigNotDefineException::class, $e);
    }
}
