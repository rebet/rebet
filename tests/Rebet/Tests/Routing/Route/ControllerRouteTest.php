<?php
namespace Rebet\Tests\Routing\Route;

use Rebet\Http\Responder;
use Rebet\Routing\Route\ControllerRoute;
use Rebet\Tests\Mock\Controller\TestController;
use Rebet\Tests\RebetTestCase;

class ControllerRouteTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(ControllerRoute::class, new ControllerRoute('/foo', TestController::class));
    }

    public function test_terminate()
    {
        $route   = new ControllerRoute('/foo', TestController::class);
        $request = $this->createRequestMock('/foo');
        $this->assertNull($route->terminate($request, Responder::toResponse('foo')));
    }
}
