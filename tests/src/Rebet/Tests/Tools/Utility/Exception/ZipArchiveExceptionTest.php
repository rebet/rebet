<?php
namespace Rebet\Tests\Tools\Utility\Exception;

use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Utility\Exception\ZipArchiveException;

class ZipArchiveExceptionTest extends RebetTestCase
{
    public function test___construct()
    {
        $e = new ZipArchiveException('test');
        $this->assertInstanceOf(ZipArchiveException::class, $e);
    }
}
