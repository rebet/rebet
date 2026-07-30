<?php
namespace Rebet\Tests\Http\Session\Storage;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Rebet\Http\Session\Storage\SessionStorage;
use Rebet\Tests\RebetTestCase;

class SessionStorageTest extends RebetTestCase
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test___construct()
    {
        $this->assertInstanceOf(SessionStorage::class, new SessionStorage());
    }
}
