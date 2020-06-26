<?php
namespace Rebet\Tests\Http\Session\Storage\Handler;

use Rebet\Http\Session\Storage\Handler\NullSessionHandler;
use Rebet\Tests\RebetTestCase;

class NullSessionHandlerTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(NullSessionHandler::class, new NullSessionHandler());
    }
}
