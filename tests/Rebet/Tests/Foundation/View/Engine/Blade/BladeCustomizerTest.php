<?php
namespace Rebet\Tests\Foundation\View\Engine\Blade;

use Rebet\Foundation\App;
use Rebet\Tests\Foundation\View\Engine\EngineCustomizerTestCase;
use Rebet\View\Engine\Blade\Blade;
use Rebet\View\Engine\Engine;

class BladeCustomizerTest extends EngineCustomizerTestCase
{
    protected function createEngine() : Engine
    {
        return new Blade([
            'view_path'  => App::path('/resources/views/blade'),
            'cache_path' => 'vfs://root/cache',
        ], true);
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
