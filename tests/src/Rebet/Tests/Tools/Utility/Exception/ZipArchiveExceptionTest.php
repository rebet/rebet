<?php

declare(strict_types=1);

namespace Rebet\Tests\Tools\Utility\Exception;

use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Utility\Exception\ZipArchiveException;

class ZipArchiveExceptionTest extends RebetTestCase
{
    public function test___construct(): void
    {
        $e = new ZipArchiveException('test');
        $this->assertInstanceOf(ZipArchiveException::class, $e);
    }
}
