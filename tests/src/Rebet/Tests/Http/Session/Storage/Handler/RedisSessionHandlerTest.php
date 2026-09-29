<?php

declare(strict_types=1);

namespace Rebet\Tests\Http\Session\Storage\Handler;

use Rebet\Http\Session\Storage\Handler\RedisSessionHandler;
use Rebet\Tests\RebetTestCase;

class RedisSessionHandlerTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(RedisSessionHandler::class, new RedisSessionHandler($this->getMockBuilder('Redis')->getMock()));
    }
}
