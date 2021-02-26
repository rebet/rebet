<?php
namespace Rebet\Tests\Application\Bootstrap;

use Rebet\Application\App;
use Rebet\Application\Bootstrap\LoadEnvironmentVariables;
use Rebet\Application\Kernel;
use Rebet\Application\Structure;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Utility\Env;

class LoadEnvironmentVariablesTest extends RebetTestCase
{
    public function test_bootstrap()
    {
        $structure = $this->createMock(Structure::class);
        $structure->method('env')->willReturn(App::structure()->resources('/adhoc/Application/Bootstrap/LoadEnvironmentVariables'));
        $kernel = $this->createMock(Kernel::class);
        $kernel->method('structure')->willReturn($structure);
        
        $this->assertNull(Env::get('CUSTOM_VALUE'));

        $bootstrapper = new LoadEnvironmentVariables();
        $bootstrapper->bootstrap($kernel);

        $this->assertSame('foo', Env::get('CUSTOM_VALUE'));
    }
}
