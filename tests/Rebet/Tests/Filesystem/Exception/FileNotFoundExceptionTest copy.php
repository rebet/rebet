<?php
namespace Rebet\Tests\Filesystem\Exception;

use Rebet\Filesystem\Exception\FileNotFoundException;
use Rebet\Tests\RebetTestCase;

class FileNotFoundExceptionTest extends RebetTestCase
{
    public function test___construct()
    {
        $e = new FileNotFoundException('test');
        $this->assertInstanceOf(FileNotFoundException::class, $e);
    }
}
