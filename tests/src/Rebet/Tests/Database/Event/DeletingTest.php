<?php
namespace Rebet\Tests\Database\Event;

use Rebet\Database\Dao;
use Rebet\Database\Event\Deleting;
use Rebet\Tests\RebetDatabaseTestCase;
use TestApp\Model\User;

class DeletingTest extends RebetDatabaseTestCase
{
    public function test___construct()
    {
        $db     = Dao::db();
        $entity = new User();
        $event  = new Deleting($db, $entity);
        $this->assertInstanceOf(Deleting::class, $event);
        $this->assertSame($db, $event->db);
        $this->assertSame($entity, $event->old);
    }
}
