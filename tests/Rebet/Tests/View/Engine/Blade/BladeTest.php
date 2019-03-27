<?php
namespace Rebet\Tests\View\Engine\Blade;

use Illuminate\View\Compilers\BladeCompiler as LaravelBladeCompiler;

use Rebet\Foundation\App;
use Rebet\Tests\RebetTestCase;
use Rebet\View\Engine\Blade\Blade;
use Rebet\View\EofLineFeed;

class BladeTest extends RebetTestCase
{
    /**
     * @var Rebet\View\Engine\Blade\Blade
     */
    private $blade;

    public function setUp()
    {
        parent::setUp();
        $this->vfs([
            'cache' => [],
        ]);

        $this->blade = new Blade([
            'view_path'  => App::path('/resources/views/blade'),
            'cache_path' => 'vfs://root/cache',
        ], true);
    }

    public function test_compiler()
    {
        $this->assertInstanceOf(LaravelBladeCompiler::class, $this->blade->compiler());
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
Hello, Samantha.
EOS
            ,
            EofLineFeed::TRIM()->process($this->blade->render('welcome', ['name' => 'Samantha']))
        );
    }

    public function dataBuiltins() : array
    {
        return [
            [
                <<<EOS
Title:
Unit Test
Section:
    - Main Section

    - Sub Section
Content:
    This is content.
EOS
                , 'builtin/child'
                , []
            ],
            [
                <<<EOS
Component Test
* Forbidden *
-----
You are not allowed to access this resource!
EOS
                , 'builtin/component'
                , []
            ],
            [
                <<<EOS
Component Args Test
* Forbidden *
-----
You are not allowed to access this resource!
EOS
                , 'builtin/component-args'
                , []
            ],
            [
                <<<EOS
var app = [1,2,3];
EOS
                , 'builtin/json'
                , ['array' => [1, 2, 3]]
            ],
        ];
    }

    /**
     * @dataProvider dataBuiltins
     */
    public function test_render_builtin(string $expect, string $name, array $args = [])
    {
        $this->assertSame($expect, EofLineFeed::TRIM()->process($this->blade->render($name, $args)));
    }
}
