<?php
namespace Rebet\Tests\View\Engine\Smarty;

use org\bovigo\vfs\vfsStream;
use Rebet\Foundation\App;

use Rebet\Tests\RebetTestCase;
use Rebet\View\Engine\Smarty\Smarty;

class SmartyTest extends RebetTestCase
{
    private $root;

    /**
     * @var Rebet\View\Engine\Smarty\Smarty
     */
    private $smarty;

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

        $this->smarty = new Smarty([
            'template_dir' => App::path('/resources/views/smarty'),
            'compile_dir'  => 'vfs://root/cache',
        ]);
    }

    public function test_render()
    {
        $this->assertSame(
            <<<EOS
Hello, Samantha.
EOS
            ,
            $this->smarty->render('welcome', ['name' => 'Samantha'])
        );
    }

    public function test_render_customizer()
    {
        // Register 'env' plugin in App::initFrameworkConfig()
        App::setEnv('unittest');
        $this->assertSame(
            <<<EOS
unittest
unittest or local
Not production.

EOS
            ,
            $this->smarty->render('custom/env')
        );

        App::setEnv('local');
        $this->assertSame(
            <<<EOS
unittest or local
Not production.

EOS
            ,
            $this->smarty->render('custom/env')
        );
    }
}
