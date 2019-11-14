<?php
namespace Rebet\Tests\Database\Event;

use Rebet\Database\Event\Created;
use Rebet\Database\Event\Saved;
use Rebet\Tests\Mock\Entity\User;
use Rebet\Tests\RebetDatabaseTestCase;

class CreatedTest extends RebetDatabaseTestCase
{
    public function test___construct()
    {
        $db     = $this->connect();
        $entity = new User();
        $event  = new Created($db, $entity);
        $this->assertInstanceOf(Created::class, $event);
        $this->assertInstanceOf(Saved::class, $event);
        $this->assertSame($db, $event->db);
        $this->assertSame($entity, $event->new);
    }
}
