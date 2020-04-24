<?php
namespace Rebet\Tests\Application\View\Engine\Blade;

use Rebet\Config\Config;
use Rebet\Application\App;
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
                'view_path>'  => [App::path('/resources/views/blade')],
                'cache_path'  => 'vfs://root/cache',
            ],
        ]);

        return new Blade(true);
    }

    /**
     * @expectedException Rebet\Common\Exception\LogicException
     * @expectedExceptionMessage Unsupported directive '@auth' found. In Rebet, you should use '@role' directive instead.
     */
    public function test_disabled_tag_auth()
    {
        $this->engine->render('disabled/auth');
    }

    /**
     * @expectedException Rebet\Common\Exception\LogicException
     * @expectedExceptionMessage Unsupported directive '@guest' found. In Rebet, you should use '@role' directive instead.
     */
    public function test_disabled_tag_guest()
    {
        $this->engine->render('disabled/guest');
    }
}
