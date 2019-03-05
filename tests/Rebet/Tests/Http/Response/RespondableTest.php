<?php
namespace Rebet\Tests\Http\Response;

use Rebet\Common\Reflector;
use Rebet\Http\Response\BasicResponse;
use Rebet\Http\Response\Respondable;
use Rebet\Tests\RebetTestCase;

class RespondableTest extends RebetTestCase
{
    public function test_getHeader()
    {
        $response = new BasicResponse('Content', 200, [
            'Content-Type' => 'text/html',
            'X-Test'       => 'This is test',
        ]);
        $this->assertTrue(Reflector::uses($response, Respondable::class));
        $this->assertSame('text/html', $response->getHeader('Content-Type'));
        $this->assertSame('This is test', $response->getHeader('X-Test'));
        $this->assertSame(null, $response->getHeader('X-Nothing'));
    }

    public function test_setHeader()
    {
        $response = new BasicResponse();
        $this->assertSame(null, $response->getHeader('X-Test'));
        $response->setHeader('X-Test', 'a');
        $this->assertSame('a', $response->getHeader('X-Test'));
        $response->setHeader('X-Test', 'b');
        $this->assertSame('b', $response->getHeader('X-Test'));
        $response->setHeader('X-Test', 'c', false);
        $this->assertSame(['b', 'c'], $response->getHeader('X-Test'));
        $this->assertSame('b', $response->getHeader('X-Test', true));
    }
}
