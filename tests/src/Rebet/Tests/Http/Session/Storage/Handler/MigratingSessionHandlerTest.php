<?php
namespace Rebet\Tests\Http\Session\Storage\Handler;

use Rebet\Http\Session\Storage\Handler\MemcachedSessionHandler;
use Rebet\Http\Session\Storage\Handler\MigratingSessionHandler;
use Rebet\Http\Session\Storage\Handler\NullSessionHandler;
use Rebet\Tests\RebetTestCase;

class MigratingSessionHandlerTest extends RebetTestCase
{
    public function test___construct()
    {
        $current = new NullSessionHandler();
        $new     = new MemcachedSessionHandler($this->getMockBuilder('Memcached')->getMock());
        $this->assertInstanceOf(MigratingSessionHandler::class, new MigratingSessionHandler($current, $new));
    }
}
