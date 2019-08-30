<?php
namespace Rebet\Tests\Database\Event;

use Rebet\Database\Event\Saving;
use Rebet\Database\Event\Updating;
use Rebet\Tests\Mock\User;
use Rebet\Tests\RebetDatabaseTestCase;

class UpdatingTest extends RebetDatabaseTestCase
{
    public function test___construct()
    {
        $db    = $this->connect();
        $old   = new User();
        $new   = new User();
        $event = new Updating($db, $old, $new);
        $this->assertInstanceOf(Updating::class, $event);
        $this->assertInstanceOf(Saving::class, $event);
        $this->assertSame($db, $event->db);
        $this->assertSame($old, $event->old);
        $this->assertSame($new, $event->new);
    }
}
