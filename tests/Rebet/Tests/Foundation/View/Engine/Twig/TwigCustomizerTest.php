<?php
namespace Rebet\Tests\Foundation\View\Engine\Twig;

use Rebet\Foundation\App;
use Rebet\Tests\Foundation\View\Engine\EngineCustomizerTestCase;
use Rebet\View\Engine\Engine;
use Rebet\View\Engine\Twig\Twig;

class TwigCustomizerTest extends EngineCustomizerTestCase
{
    protected function createEngine() : Engine
    {
        return new Twig([
            'template_dir' => App::path('/resources/views/twig'),
            'options'      => [
                // 'cache' => 'vfs://root/cache',
            ],
        ], true);
    }
}
