<?php
namespace Rebet\Tests\View\Engine\Blade\Compiler;

use Rebet\Foundation\App;
use Rebet\Tests\RebetTestCase;
use Rebet\View\Engine\Blade\Blade;
use Rebet\View\Engine\Blade\Compiler\BladeCompiler;

class BladeCompilerTest extends RebetTestCase
{
    /**
     * @var BladeCompiler
     */
    private $compiler;

    public function setUp()
    {
        parent::setUp();
        $this->vfs([
            'cache' => [],
        ]);

        $blade = new Blade([
            'view_path'  => App::path('/resources/views/blade'),
            'cache_path' => 'vfs://root/cache',
        ], true);
        $this->compiler = $blade->compiler();
    }

    public function test_raw()
    {
        $this->assertSame(null, $this->compiler->getCustomDirectives()['hello'] ?? null);
        $this->compiler->raw('hello', "echo('Hello');");
        $this->assertSame("<?php echo('Hello'); ?>", call_user_func($this->compiler->getCustomDirectives()['hello']));
    }

    public function test_code()
    {
        $this->assertSame(null, $this->compiler->getCustomDirectives()['hello'] ?? null);
        $this->compiler->code('hello', "echo(", function () { return 'Hello'; }, ');');
        $this->assertSame("<?php echo( \Illuminate\Support\Facades\Blade::execute('hello') ); ?>", call_user_func($this->compiler->getCustomDirectives()['hello'], null));

        $this->compiler->code('say', "echo(", function ($word) { return $word; }, ');');
        $this->assertSame("<?php echo( \Illuminate\Support\Facades\Blade::execute('say', 'hello') ); ?>", call_user_func($this->compiler->getCustomDirectives()['say'], "'hello'"));

        $this->compiler->code('welcom', "echo(", function ($user_name, $word) { return "{$word} {$user_name}"; }, ');', '$user_name');
        $this->assertSame("<?php echo( \Illuminate\Support\Facades\Blade::execute('welcom', \$user_name, 'hello') ); ?>", call_user_func($this->compiler->getCustomDirectives()['welcom'], "'hello'"));
    }

    public function test_execute()
    {
        $this->compiler->code('say', "echo(", function ($word) { return $word; }, ');');
        $this->assertSame('Hello', $this->compiler->execute('say', 'Hello'));
    }

    public function test_if()
    {
        $this->compiler->if('hello', function ($word) { return $word === 'hello'; });
        $directives = $this->compiler->getCustomDirectives();
        $this->assertSame("<?php if (\Illuminate\Support\Facades\Blade::check('hello', 'welcom')): ?>", call_user_func($directives['hello'], "'welcom'"));
        $this->assertSame("<?php elseif (\Illuminate\Support\Facades\Blade::check('hello', 'bye')): ?>", call_user_func($directives['elsehello'], "'bye'"));
        $this->assertSame("<?php endif; ?>", call_user_func($directives['endhello']));
        $this->assertSame("<?php if (! \Illuminate\Support\Facades\Blade::check('hello', 'welcom')): ?>", call_user_func($directives['hellonot'], "'welcom'"));
        $this->assertSame("<?php elseif (! \Illuminate\Support\Facades\Blade::check('hello', 'bye')): ?>", call_user_func($directives['elsehellonot'], "'bye'"));
        $this->assertSame(true, $this->compiler->check('hello', 'hello'));
        $this->assertSame(false, $this->compiler->check('hello', 'welcom'));

        $this->compiler->if('say', function ($word, $expect) { return $word === $expect; }, '$word');
        $directives = $this->compiler->getCustomDirectives();
        $this->assertSame("<?php if (\Illuminate\Support\Facades\Blade::check('say', \$word, 'welcom')): ?>", call_user_func($directives['say'], "'welcom'"));
        $this->assertSame("<?php elseif (\Illuminate\Support\Facades\Blade::check('say', \$word, 'bye')): ?>", call_user_func($directives['elsesay'], "'bye'"));
        $this->assertSame("<?php endif; ?>", call_user_func($directives['endsay']));
        $this->assertSame("<?php if (! \Illuminate\Support\Facades\Blade::check('say', \$word, 'welcom')): ?>", call_user_func($directives['saynot'], "'welcom'"));
        $this->assertSame("<?php elseif (! \Illuminate\Support\Facades\Blade::check('say', \$word, 'bye')): ?>", call_user_func($directives['elsesaynot'], "'bye'"));
        $this->assertSame(true, $this->compiler->check('say', 'hello', 'hello'));
        $this->assertSame(false, $this->compiler->check('say', 'welcom', 'hello'));
    }

    /**
     * @expectedException Rebet\Common\Exception\LogicException
     * @expectedExceptionMessage The 'hello' directive is not supported in Rebet.
     */
    public function test_disable()
    {
        $this->compiler->disable('hello');
        $directives = $this->compiler->getCustomDirectives();
        call_user_func($directives['hello'], null);
    }
}
