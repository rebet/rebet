<?php
namespace Rebet\Tests\Database\Event;

use Rebet\Database\Dao;
use Rebet\Database\Event\Saving;
use Rebet\Database\Event\Updating;
use Rebet\Tests\RebetDatabaseTestCase;
use TestApp\Model\User;

class UpdatingTest extends RebetDatabaseTestCase
{
    public function test___construct()
    {
        $db    = Dao::db();
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
