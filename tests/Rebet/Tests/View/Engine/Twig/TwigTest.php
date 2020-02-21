<?php
namespace Rebet\Tests\View\Engine\Twig;

use Rebet\Config\Config;
use Rebet\Foundation\App;

use Rebet\Tests\RebetTestCase;
use Rebet\View\Engine\Twig\Twig;

class TwigTest extends RebetTestCase
{
    /**
     * @var Twig
     */
    private $twig;

    public function setUp()
    {
        parent::setUp();
        $this->vfs([
            'cache' => [],
        ]);
        Config::application([
            Twig::class => [
                'template_dir' => [App::path('/resources/views/twig')],
                'options'      => [
                    // 'cache' => 'vfs://root/cache',
                ],
            ],
        ]);

        $this->twig = new Twig(true);
    }

    public function test_getPaths()
    {
        $this->assertTrue(in_array(App::path('/resources/views/twig'), $this->twig->getPaths()));
    }

    public function test_prependPath()
    {
        $paths = $this->twig->getPaths();
        $this->twig->prependPath($path_1 = App::path('/resources/views'));
        $new_paths = $this->twig->getPaths();
        $this->assertSame(array_merge([$path_1], $paths), $new_paths);
    }

    public function test_appendPath()
    {
        $paths = $this->twig->getPaths();
        $this->twig->appendPath($path_1 = App::path('/resources/views'));
        $new_paths = $this->twig->getPaths();
        $this->assertSame(array_merge($paths, [$path_1]), $new_paths);
    }

    public function test_exists()
    {
        $this->assertTrue($this->twig->exists('welcome'));
        $this->assertTrue($this->twig->exists('custom/env'));
        $this->assertFalse($this->twig->exists('nothing'));
    }

    public function test_render()
    {
        $this->assertSame(
            <<<EOS
Hello, Samantha.
EOS
            ,
            $this->twig->render('welcome', ['name' => 'Samantha'])
        );
    }
}
