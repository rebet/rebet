<?php
namespace Rebet\Tests\File\Exception;

use Rebet\File\Exception\ZipArchiveException;
use Rebet\Tests\RebetTestCase;

class ZipArchiveExceptionTest extends RebetTestCase
{
    public function test___construct()
    {
        $e = new ZipArchiveException('test');
        $this->assertInstanceOf(ZipArchiveException::class, $e);
    }
}
