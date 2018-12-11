<?php
namespace Rebet\Tests\View\Engine\Blade;

use Illuminate\View\Compilers\BladeCompiler;
use org\bovigo\vfs\vfsStream;

use Rebet\Foundation\App;
use Rebet\Tests\RebetTestCase;
use Rebet\View\Engine\Blade\Blade;

class BladeTest extends RebetTestCase
{
    private $root;

    /**
     * @var Rebet\View\Engine\Blade\Blade
     */
    private $blade;

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

        $this->blade = new Blade([
            'view_path'  => App::path('/resources/views/blade'),
            'cache_path' => 'vfs://root/cache',
        ]);
    }

    public function test_compiler()
    {
        $this->assertInstanceOf(BladeCompiler::class, $this->blade->compiler());
    }

    public function test_exists()
    {
        $this->assertTrue($this->blade->exists('welcome'));
        $this->assertTrue($this->blade->exists('custom/env'));
        $this->assertFalse($this->blade->exists('nothing'));
    }

    public function test_render()
    {
        $this->assertSame(
            <<<EOS
Title:
Unit Test
Section:
    - Main Section

    - Sub Section
Content:
    This is content.

EOS
            ,
            $this->blade->render('child')
        );

        $this->assertSame(
            <<<EOS
Component Test
* Forbidden *
-----
You are not allowed to access this resource!
EOS
            ,
            $this->blade->render('component')
        );

        $this->assertSame(
            <<<EOS
Component Args Test
* Forbidden *
-----
You are not allowed to access this resource!
EOS
            ,
            $this->blade->render('component-args')
        );

        $this->assertSame(
            <<<EOS
Hello, Samantha.
EOS
            ,
            $this->blade->render('welcome', ['name' => 'Samantha'])
        );

        $this->assertSame(
            <<<EOS
var app = [1,2,3];
EOS
            ,
            $this->blade->render('json', ['array' => [1, 2, 3]])
        );
    }

    public function test_render_directive()
    {
        $this->blade = new Blade([
            'view_path'   => App::path('/resources/views/blade'),
            'cache_path'  => 'vfs://root/cache',
            'customizers' => [function (BladeCompiler $blade) {
                $blade->directive('hello', function ($word) {
                    return "Hello {$word}!";
                });
            }],
        ]);

        $this->assertSame(
            <<<EOS
Hello World!
EOS
            ,
            $this->blade->render('hello')
        );
    }

    public function test_render_customizer_env()
    {
        // Register 'env' custom directive in App::initFrameworkConfig()
        App::setEnv('unittest');
        $this->assertSame(
            <<<EOS
unittest
unittest or local
Not production.

EOS
            ,
            $this->blade->render('custom/env')
        );

        App::setEnv('local');
        $this->assertSame(
            <<<EOS
unittest or local
Not production.

EOS
            ,
            $this->blade->render('custom/env')
        );
    }
}
