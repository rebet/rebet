<?php
namespace Rebet\Tests\Cache\Adapter\Symfony;

use Rebet\Cache\Adapter\Symfony\ArrayAdapter;
use Rebet\Tests\RebetDatabaseTestCase;

class ArrayAdapterTest extends RebetDatabaseTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(ArrayAdapter::class, new ArrayAdapter());
        $this->assertInstanceOf(ArrayAdapter::class, new ArrayAdapter('', 5));
        $this->assertInstanceOf(ArrayAdapter::class, new ArrayAdapter('', '5min'));
    }
}
