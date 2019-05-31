<?php
namespace Rebet\Tests\View\Engine\Twig;

use Rebet\Foundation\App;

use Rebet\Tests\RebetTestCase;
use Rebet\View\Engine\Twig\Environment\Environment;
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

        $this->twig = new Twig([
            'template_dir' => App::path('/resources/views/twig'),
            'options'      => [
                // 'cache' => 'vfs://root/cache',
            ],
        ], true);
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

    public function test_customize()
    {
        Twig::customize(function (Environment $env) {
            $env->raw('hello', "echo('Hello');");
        });
        $this->assertSame(
            <<<EOS
Hello
EOS
            ,
            $this->twig->render('hello')
        );
    }
}
