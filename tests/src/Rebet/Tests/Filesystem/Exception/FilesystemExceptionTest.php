<?php

declare(strict_types=1);

namespace Rebet\Tests\Filesystem\Exception;

use Rebet\Filesystem\Exception\FilesystemException;
use Rebet\Tests\RebetTestCase;

class FilesystemExceptionTest extends RebetTestCase
{
    public function test___construct()
    {
        $e = new FilesystemException('test');
        $this->assertInstanceOf(FilesystemException::class, $e);
    }
}
