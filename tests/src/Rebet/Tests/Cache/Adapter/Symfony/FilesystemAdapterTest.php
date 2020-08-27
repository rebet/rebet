<?php
namespace Rebet\Tests\Cache\Adapter\Symfony;

use Rebet\Cache\Adapter\Symfony\FilesystemAdapter;
use Rebet\Tests\RebetTestCase;

class FilesystemAdapterTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(FilesystemAdapter::class, new FilesystemAdapter());
        $this->assertInstanceOf(FilesystemAdapter::class, new FilesystemAdapter('', 5));
        $this->assertInstanceOf(FilesystemAdapter::class, new FilesystemAdapter('', '5min'));
    }
}
