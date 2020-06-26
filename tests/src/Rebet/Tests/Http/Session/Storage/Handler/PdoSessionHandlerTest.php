<?php
namespace Rebet\Tests\Http\Session\Storage\Handler;

use Rebet\Http\Session\Storage\Handler\PdoSessionHandler;
use Rebet\Tests\RebetTestCase;

class PdoSessionHandlerTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(PdoSessionHandler::class, new PdoSessionHandler());
    }
}
