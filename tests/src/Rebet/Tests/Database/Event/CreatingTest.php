<?php
namespace Rebet\Tests\Database\Event;

use App\Model\User;
use Rebet\Database\Dao;
use Rebet\Database\Event\Creating;
use Rebet\Database\Event\Saving;
use Rebet\Tests\RebetDatabaseTestCase;

class CreatingTest extends RebetDatabaseTestCase
{
    public function test___construct()
    {
        $db     = Dao::db();
        $entity = new User();
        $event  = new Creating($db, $entity);
        $this->assertInstanceOf(Creating::class, $event);
        $this->assertInstanceOf(Saving::class, $event);
        $this->assertSame($db, $event->db);
        $this->assertSame($entity, $event->new);
    }
}
