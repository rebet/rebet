<?php
namespace Rebet\Tests\Http\Session\Storage\Proxy;

use Rebet\Http\Session\Storage\Handler\NullSessionHandler;
use Rebet\Http\Session\Storage\Proxy\SessionHandlerProxy;
use Rebet\Tests\RebetTestCase;

class SessionHandlerProxyTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(SessionHandlerProxy::class, new SessionHandlerProxy(new NullSessionHandler()));
    }
}
