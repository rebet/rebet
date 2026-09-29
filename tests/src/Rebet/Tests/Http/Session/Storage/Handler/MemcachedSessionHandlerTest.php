<?php

declare(strict_types=1);

namespace Rebet\Tests\Http\Session\Storage\Handler;

use Rebet\Http\Session\Storage\Handler\MemcachedSessionHandler;
use Rebet\Tests\RebetTestCase;

class MemcachedSessionHandlerTest extends RebetTestCase
{
    public function test___construct(): void
    {
        $memcached = $this->getMockBuilder('Memcached')->getMock();
        $this->assertInstanceOf(MemcachedSessionHandler::class, new MemcachedSessionHandler($memcached));
    }
}
