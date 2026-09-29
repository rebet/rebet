<?php

declare(strict_types=1);

namespace Rebet\Tests\Database;

use Rebet\Database\Dao;
use Rebet\Database\Query;
use Rebet\Tests\RebetDatabaseTestCase;

class QueryTest extends RebetDatabaseTestCase
{
    public function test___construct(): void
    {
        $query = new Query(Dao::db()->driver(), 'param = :param', ['param' => 'value']);
        $this->assertInstanceOf(Query::class, $query);
    }

    public function test_asWhere(): void
    {
        $driver = Dao::db()->driver();
        $query  = new Query($driver, '');
        $this->assertEquals('', $query->asWhere());

        $query = new Query($driver, 'param = :param', ['param' => 'value']);
        $this->assertEquals(' WHERE param = :param', $query->asWhere());
    }
}
