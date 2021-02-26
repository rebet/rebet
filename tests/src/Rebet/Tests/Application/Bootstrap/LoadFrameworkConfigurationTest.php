<?php
namespace Rebet\Tests\Application\Bootstrap;

use Rebet\Application\App;
use Rebet\Application\Bootstrap\LoadFrameworkConfiguration;
use Rebet\Http\Session\Storage\Handler\NativeFileSessionHandler;
use Rebet\Http\Session\Storage\SessionStorage;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Config\Config;
use Rebet\Tools\Config\Layer;

class LoadFrameworkConfigurationTest extends RebetTestCase
{
    public function test_bootstrap()
    {
        Config::clear(SessionStorage::class, Layer::FRAMEWORK);

        $this->assertSame(null, SessionStorage::config('handler', false));

        $bootstrapper = new LoadFrameworkConfiguration();
        $bootstrapper->bootstrap(App::kernel());

        $this->assertSame(NativeFileSessionHandler::class, SessionStorage::config('handler', false));
    }
}
