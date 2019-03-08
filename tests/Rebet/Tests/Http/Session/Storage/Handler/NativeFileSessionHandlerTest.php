<?php
namespace Rebet\Tests\Http\Session\Storage\Handler;

use Rebet\Http\Session\Storage\Handler\NativeFileSessionHandler;
use Rebet\Tests\RebetTestCase;

class NativeFileSessionHandlerTest extends RebetTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->vfs([
            'session' => []
        ]);
    }

    public function test___construct()
    {
        $this->assertInstanceOf(NativeFileSessionHandler::class, new NativeFileSessionHandler('vfs://root/session'));
    }
}
