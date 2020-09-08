<?php
namespace Rebet\Tests\Http\Session\Storage\Handler;

use Rebet\Http\Session\Storage\Handler\NativeFileSessionHandler;
use Rebet\Tests\RebetTestCase;

class NativeFileSessionHandlerTest extends RebetTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        $this->vfs([
            'session' => []
        ]);
    }

    /**
     * @runInSeparateProcess
     */
    public function test___construct()
    {
        $this->assertInstanceOf(NativeFileSessionHandler::class, new NativeFileSessionHandler('vfs://root/session'));
    }
}
