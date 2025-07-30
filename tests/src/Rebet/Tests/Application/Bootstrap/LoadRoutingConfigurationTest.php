<?php
namespace Rebet\Tests\Application\Bootstrap;

use Rebet\Application\App;
use Rebet\Application\Bootstrap\LoadRoutingConfiguration;
use Rebet\Application\Kernel;
use Rebet\Application\Structure;
use Rebet\Routing\Exception\RouteNotFoundException;
use Rebet\Routing\Router;
use Rebet\Tests\RebetTestCase;

class LoadRoutingConfigurationTest extends RebetTestCase
{
    public function test_bootstrap()
    {
        $request = $this->createRequestMock('/hello');
        try {
            $response = Router::handle($request);
            $this->fail('Never reached.');
        } catch(RouteNotFoundException $e) {
            $this->assertSame('Route GET /hello not found.', $e->getMessage());
        }

        $structure = $this->createMock(Structure::class);
        $structure->method('routes')->willReturn(App::structure()->resources('/adhoc/Application/Bootstrap/LoadRoutingConfiguration'));
        $kernel = $this->createMock(Kernel::class);
        $kernel->method('structure')->willReturn($structure);
        
        $bootstrapper = new LoadRoutingConfiguration();
        $bootstrapper->bootstrap($kernel);

        $response = Router::handle($request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Hello World.', $response->getContent());
    }
}
