<?php
namespace Rebet\Tests\Application\Bootstrap;

use Rebet\Application\App;
use Rebet\Application\Bootstrap\PropertiesMaskingConfiguration;
use Rebet\Log\Driver\Monolog\Formatter\TextFormatter;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Config\Config;
use Rebet\Tools\Config\Layer;

class PropertiesMaskingConfigurationTest extends RebetTestCase
{
    public function test_bootstrap()
    {
        Config::clear(TextFormatter::class, Layer::FRAMEWORK);
        $this->assertSame([], TextFormatter::config('masks', false));

        $bootstrapper = new PropertiesMaskingConfiguration(['password', 'password_confirm']);
        $bootstrapper->bootstrap(App::kernel());

        $this->assertSame(['password', 'password_confirm'], TextFormatter::config('masks', false));
    }
}
