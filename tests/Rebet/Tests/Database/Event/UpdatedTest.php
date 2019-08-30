<?php
namespace Rebet\Tests\Database\Event;

use Rebet\Database\Event\Saved;
use Rebet\Database\Event\Updated;
use Rebet\Tests\Mock\User;
use Rebet\Tests\RebetDatabaseTestCase;

class UpdatedTest extends RebetDatabaseTestCase
{
    public function test___construct()
    {
        $db    = $this->connect();
        $old   = new User();
        $new   = new User();
        $event = new Updated($db, $old, $new);
        $this->assertInstanceOf(Updated::class, $event);
        $this->assertInstanceOf(Saved::class, $event);
        $this->assertSame($db, $event->db);
        $this->assertSame($old, $event->old);
        $this->assertSame($new, $event->new);
    }
}
