<?php
namespace Rebet\Tests\View\Engine\Twig;

use Rebet\Tests\RebetTestCase;
use Rebet\View\Engine\Twig\Twig;

use org\bovigo\vfs\vfsStream;
use Rebet\Foundation\App;
use Rebet\View\View;
use Rebet\Config\Config;

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
                'view' => [
                    'welcome.twig' => <<<'EOS'
Hello, {{name}}.
EOS
                    ,
                    'custom.twig' => <<<'EOS'
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

        $this->twig = new Twig([
            'template_dir' => 'vfs://root/view',
            'options'      => [
                // 'cache' => 'vfs://root/cache',
            ],
        ]);
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

//     public function test_render_directiveItterable()
//     {
//         // Register 'env' plugin dir in App::initFrameworkConfig()
//         App::setEnv('unittest');
//         $this->assertSame(
//             <<<EOS
// unittest
// unittest or local
// Not production.

// EOS
//             ,
//             $this->twig->render('custom')
//         );

//         App::setEnv('local');
//         $this->assertSame(
//             <<<EOS
// unittest or local
// Not production.

// EOS
//             ,
//             $this->twig->render('custom')
//         );
//    }

    // /**
    //  * @expectedException \LogicException
    //  * @expectedExceptionMessage Invalid path format: c:/invalid/../../path
    //  */
    // public function test_normalizePath_invalid()
    // {
    // }
}
