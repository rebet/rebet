<?php
namespace Rebet\Tests\Http\Session\Storage;

use Rebet\Http\Session\Storage\SessionStorage;
use Rebet\Tests\RebetTestCase;

class SessionStorageTest extends RebetTestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test___construct()
    {
        $this->assertInstanceOf(SessionStorage::class, new SessionStorage());
    }
}
