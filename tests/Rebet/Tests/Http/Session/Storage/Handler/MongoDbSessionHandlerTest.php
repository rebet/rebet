<?php
namespace Rebet\Tests\Http\Session\Storage\Handler;

use Rebet\Http\Session\Storage\Handler\MongoDbSessionHandler;
use Rebet\Tests\RebetTestCase;

class MongoDbSessionHandlerTest extends RebetTestCase
{
    public function test___construct()
    {
        $mongo = $this->getMockBuilder('\MongoDB\Client')->getMock();
        $this->assertInstanceOf(MongoDbSessionHandler::class, new MongoDbSessionHandler($mongo, [
            'database'   => 'mongodb://localhost:27017/test',
            'collection' => 'test',
        ]));
    }
}
