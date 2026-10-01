<?php

declare(strict_types=1);

namespace Rebet\Tests\Http\Session\Storage\Handler;

use Rebet\Http\Session\Storage\Handler\MemcachedSessionHandler;
use Rebet\Http\Session\Storage\Handler\MigratingSessionHandler;
use Rebet\Http\Session\Storage\Handler\NullSessionHandler;
use Rebet\Tests\RebetTestCase;

class MigratingSessionHandlerTest extends RebetTestCase
{
    public function test___construct(): void
    {
        $current = new NullSessionHandler();
        $new     = new MemcachedSessionHandler('memcached://memcached-session:11211');
        $this->assertInstanceOf(MigratingSessionHandler::class, new MigratingSessionHandler($current, $new));
    }
}
