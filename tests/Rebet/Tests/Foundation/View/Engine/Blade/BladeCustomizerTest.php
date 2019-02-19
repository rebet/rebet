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
        ]);
    }
}
