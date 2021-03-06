<?php
namespace Rebet\Tests\Application\View\Engine\Twig;

use Rebet\Application\App;
use Rebet\Tools\Config\Config;
use Rebet\Tests\Application\View\Engine\EngineCustomizerTestCase;
use Rebet\View\Engine\Engine;
use Rebet\View\Engine\Twig\Twig;
use Rebet\View\View;

class TwigTagCustomizerTest extends EngineCustomizerTestCase
{
    protected function createEngine() : Engine
    {
        Config::application([
            View::class => [
                'engine' => Twig::class,
            ],
            Twig::class => [
                'template_dir' => [App::structure()->views('/twig')],
                'options'      => [
                    // 'cache' => 'vfs://root/cache',
                ],
            ],
        ]);

        return new Twig(true);
    }
}
