<?php
namespace Rebet\Tests\Routing;

use Rebet\Http\Responder;
use Rebet\Routing\Controller;
use Rebet\Tests\RebetTestCase;

class ControllerTest extends RebetTestCase
{
    /**
     * @var Controller
     */
    private $controller;

    protected function setUp() : void
    {
        parent::setUp();
        $this->controller = new class extends Controller {
            // Nothing to override.
        };
    }

    public function test_before()
    {
        $request = $this->createRequestMock('/');
        $this->assertSame($request, $this->controller->before($request));
    }

    public function test_after()
    {
        $request  = $this->createRequestMock('/');
        $response = Responder::toResponse('Hello');
        $this->assertSame($response, $this->controller->after($request, $response));
    }
}
