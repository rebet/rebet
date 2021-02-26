<?php
namespace Rebet\Tests\Application\Bootstrap;

use Rebet\Application\App;
use Rebet\Application\Bootstrap\LoadApplicationConfiguration;
use Rebet\Application\Kernel;
use Rebet\Application\Structure;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Config\Config;
use Rebet\Tools\Config\Layer;

class LoadApplicationConfigurationTest extends RebetTestCase
{
    public function test_bootstrap()
    {
        $structure = $this->createMock(Structure::class);
        $structure->method('configs')->willReturn(App::structure()->resources('/adhoc/Application/Bootstrap/LoadApplicationConfiguration'));
        $kernel = $this->createMock(Kernel::class);
        $kernel->method('structure')->willReturn($structure);
        
        $this->assertNull(App::config('custom_value', false));

        $bootstrapper = new LoadApplicationConfiguration();
        $bootstrapper->bootstrap($kernel);

        $this->assertSame('foo', App::config('custom_value', false));

        Config::clear(App::class, Layer::APPLICATION);

        $this->assertNull(App::config('custom_value', false));
    }
}
