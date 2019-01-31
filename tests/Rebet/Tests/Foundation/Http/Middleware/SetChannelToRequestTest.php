<?php
namespace Rebet\Tests\Foundation\Http\Middleware;

use Rebet\Foundation\App;
use Rebet\Foundation\Http\Middleware\SetChannelToRequest;
use Rebet\Http\Responder;
use Rebet\Http\Response\BasicResponse;
use Rebet\Tests\RebetTestCase;

class SetChannelToRequestTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(SetChannelToRequest::class, new SetChannelToRequest());
    }

    public function test_handle()
    {
        App::setChannel('web');
        $middleware  = new SetChannelToRequest();
        $destination = function ($request) { return Responder::toResponse('OK'); };

        $request  = $this->createRequestMock('/', null, null);
        $this->assertSame(null, $request->channel);
        
        $response = $middleware->handle($request, $destination);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame('OK', $response->getContent());
        $this->assertSame(App::getChannel(), $request->channel);
    }
}
