<?php
namespace Rebet\Tests\Http\Session\Storage\Handler;

use Rebet\Http\Session\Storage\Handler\MongoDbSessionHandler;
use Rebet\Tests\RebetTestCase;

class MongoDbSessionHandlerTest extends RebetTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        if (!class_exists('MongoDB\Client')) {
            $this->markTestSkipped('MongoDB\Client (mongodb/mongodb package) is not installed.');
        }
    }

    public function test___construct()
    {
        $mongo = $this->getMockBuilder('\MongoDB\Client')->getMock();
        $mongo->method('getManager')->willReturn(new \MongoDB\Driver\Manager('mongodb://localhost:27017'));
        $this->assertInstanceOf(MongoDbSessionHandler::class, new MongoDbSessionHandler($mongo, [
            'database'   => 'mongodb://localhost:27017/test',
            'collection' => 'test',
        ]));
    }
}
