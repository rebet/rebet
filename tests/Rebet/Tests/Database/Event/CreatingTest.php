<?php
namespace Rebet\Tests\Database\Event;

use Rebet\Database\Event\Creating;
use Rebet\Database\Event\Saving;
use Rebet\Tests\Mock\User;
use Rebet\Tests\RebetDatabaseTestCase;

class CreatingTest extends RebetDatabaseTestCase
{
    public function test___construct()
    {
        $db     = $this->connect();
        $entity = new User();
        $event  = new Creating($db, $entity);
        $this->assertInstanceOf(Creating::class, $event);
        $this->assertInstanceOf(Saving::class, $event);
        $this->assertSame($db, $event->db);
        $this->assertSame($entity, $event->new);
    }
}
