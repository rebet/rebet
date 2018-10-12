<?php
namespace Rebet\Tests\View\Engine\Smarty;

use Rebet\Tests\RebetTestCase;
use Rebet\View\Engine\Smarty\Smarty;

use org\bovigo\vfs\vfsStream;
use Rebet\Foundation\App;
use Rebet\View\View;
use Rebet\Config\Config;

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
                'view' => [
                    'welcome.tpl' => <<<'EOS'
Hello, {$name}.
EOS
                    ,
                    'custom.tpl' => <<<'EOS'
{env in='unittest'}
unittest
{/env}
{env in=['unittest','local']}
unittest or local
{/env}
{env in='production'}
production
{/env}
{env not_in='production'}
Not production.
{/env}
EOS
                    ,
                ],
                'cache' => [],
            ],
            $this->root
        );

        $this->smarty = new Smarty([
            'template_dir' => 'vfs://root/view',
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

    public function test_render_directiveItterable()
    {
        // Register 'env' plugin dir in App::initFrameworkConfig()
        App::setEnv('unittest');
        $this->assertSame(
            <<<EOS
unittest
unittest or local
Not production.

EOS
            ,
            $this->smarty->render('custom')
        );

        App::setEnv('local');
        $this->assertSame(
            <<<EOS
unittest or local
Not production.

EOS
            ,
            $this->smarty->render('custom')
        );
    }

    // /**
    //  * @expectedException \LogicException
    //  * @expectedExceptionMessage Invalid path format: c:/invalid/../../path
    //  */
    // public function test_normalizePath_invalid()
    // {
    // }
}
