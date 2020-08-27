<?php
namespace Rebet\Tests\Cache\Adapter\Symfony;

use Rebet\Cache\Adapter\Symfony\PdoAdapter;
use Rebet\Tests\RebetDatabaseTestCase;

class PdoAdapterTest extends RebetDatabaseTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
    }

    public function test___construct()
    {
        $this->assertInstanceOf(PdoAdapter::class, new PdoAdapter('mysql'));
    }
}
