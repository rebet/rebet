<?php

declare(strict_types=1);

namespace Rebet\Tests\Routing;

use Override;
use Rebet\Http\Responder;
use Rebet\Routing\Controller;
use Rebet\Tests\RebetTestCase;

class ControllerTest extends RebetTestCase
{
    /**
     * @var Controller
     */
    private $controller;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new class extends Controller {
            // Nothing to override.
        };
    }

    public function test_before(): void
    {
        $request = $this->createRequestMock('/');
        $this->assertSame($request, $this->controller->before($request));
    }

    public function test_after(): void
    {
        $request  = $this->createRequestMock('/');
        $response = Responder::toResponse('Hello');
        $this->assertSame($response, $this->controller->after($request, $response));
    }
}
