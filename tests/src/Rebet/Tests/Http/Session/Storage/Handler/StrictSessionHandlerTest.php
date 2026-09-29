<?php

declare(strict_types=1);

namespace Rebet\Tests\Http\Session\Storage\Handler;

use Rebet\Http\Session\Storage\Handler\StrictSessionHandler;
use Rebet\Tests\RebetTestCase;

class StrictSessionHandlerTest extends RebetTestCase
{
    public function test___construct(): void
    {
        $this->assertInstanceOf(StrictSessionHandler::class, new StrictSessionHandler($this->getMockBuilder('SessionHandlerInterface')->getMock()));
    }
}
