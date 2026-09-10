<?php
namespace Rebet\Tests\Database\Event;

use Rebet\Database\Dao;
use Rebet\Database\Event\Deleted;
use Rebet\Tests\RebetDatabaseTestCase;
use TestApp\Model\User;

class DeletedTest extends RebetDatabaseTestCase
{
    public function test___construct()
    {
        $db     = Dao::db();
        $entity = new User();
        $event  = new Deleted($db, $entity);
        $this->assertInstanceOf(Deleted::class, $event);
        $this->assertSame($db, $event->db);
        $this->assertSame($entity, $event->old);
    }
}
