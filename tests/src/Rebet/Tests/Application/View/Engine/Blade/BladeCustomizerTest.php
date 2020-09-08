<?php
namespace Rebet\Tests\Application\View\Engine\Blade;

use Rebet\Application\App;
use Rebet\Common\Exception\LogicException;
use Rebet\Config\Config;
use Rebet\Tests\Application\View\Engine\EngineCustomizerTestCase;
use Rebet\View\Engine\Blade\Blade;
use Rebet\View\Engine\Engine;
use Rebet\View\View;

class BladeCustomizerTest extends EngineCustomizerTestCase
{
    protected function createEngine() : Engine
    {
        Config::application([
            View::class => [
                'engine' => Blade::class,
            ],
            Blade::class => [
                'view_path>'  => [App::structure()->views('/blade')],
                'cache_path'  => 'vfs://root/cache',
            ],
        ]);

        return new Blade(true);
    }

    public function test_disabled_tag_auth()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Unsupported directive '@auth' found. In Rebet, you should use '@role' directive instead.");

        $this->engine->render('disabled/auth');
    }

    public function test_disabled_tag_guest()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Unsupported directive '@guest' found. In Rebet, you should use '@role' directive instead.");

        $this->engine->render('disabled/guest');
    }
}
