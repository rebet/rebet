<?php

declare(strict_types=1);

namespace Rebet\Tests\Filesystem\Exception;

use Rebet\Filesystem\Exception\FileNotFoundException;
use Rebet\Tests\RebetTestCase;

class FileNotFoundExceptionTest extends RebetTestCase
{
    public function test___construct(): void
    {
        $e = new FileNotFoundException('test');
        $this->assertInstanceOf(FileNotFoundException::class, $e);
    }
}
