<?php
namespace Rebet\Tests\View\Engine\Twig;

use org\bovigo\vfs\vfsStream;
use Rebet\Foundation\App;

use Rebet\Tests\RebetTestCase;
use Rebet\View\Engine\Twig\Twig;

class TwigTest extends RebetTestCase
{
    private $root;

    /**
     * @var Rebet\View\Engine\Twig\Twig
     */
    private $Twig;

    public function setUp()
    {
        parent::setUp();
        $this->root = vfsStream::setup();
        vfsStream::create(
            [
                'cache' => [],
            ],
            $this->root
        );

        $this->twig = new Twig([
            'template_dir' => App::path('/resources/views/twig'),
            'options'      => [
                // 'cache' => 'vfs://root/cache',
            ],
        ]);
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

    public function test_render_customizer()
    {
        // Register 'env' extention in App::initFrameworkConfig()
        App::setEnv('unittest');
        $this->assertSame(
            <<<EOS
unittest
unittest or local
Not production.

EOS
            ,
            $this->twig->render('custom/env')
        );

        App::setEnv('local');
        $this->assertSame(
            <<<EOS
unittest or local
Not production.

EOS
            ,
            $this->twig->render('custom/env')
        );
    }
}
