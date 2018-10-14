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
{% if 'unittest' is env %}
unittest
{% endif %}
{% if ['unittest','local'] is env %}
unittest or local
{% endif %}
{% if 'production' is env %}
production
{% else %}
Not production.
{% endif %}
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
            $this->twig->render('custom')
        );

        App::setEnv('local');
        $this->assertSame(
            <<<EOS
unittest or local
Not production.

EOS
            ,
            $this->twig->render('custom')
        );
    }
}
