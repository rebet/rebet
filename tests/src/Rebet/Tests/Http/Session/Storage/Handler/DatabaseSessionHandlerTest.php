<?php

declare(strict_types=1);

namespace Rebet\Tests\Http\Session\Storage\Handler;

use Rebet\Http\Session\Storage\Handler\DatabaseSessionHandler;
use Rebet\Tests\RebetDatabaseTestCase;

class DatabaseSessionHandlerTest extends RebetDatabaseTestCase
{
    public function test___construct(): void
    {
        $this->assertInstanceOf(DatabaseSessionHandler::class, new DatabaseSessionHandler());
    }
}
