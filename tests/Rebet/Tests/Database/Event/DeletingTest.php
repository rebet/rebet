<?php
namespace Rebet\Tests\Database\Event;

use Rebet\Database\Event\Deleting;
use Rebet\Tests\Mock\Entity\User;
use Rebet\Tests\RebetDatabaseTestCase;

class DeletingTest extends RebetDatabaseTestCase
{
    public function test___construct()
    {
        $db     = $this->connect();
        $entity = new User();
        $event  = new Deleting($db, $entity);
        $this->assertInstanceOf(Deleting::class, $event);
        $this->assertSame($db, $event->db);
        $this->assertSame($entity, $event->old);
    }
}
