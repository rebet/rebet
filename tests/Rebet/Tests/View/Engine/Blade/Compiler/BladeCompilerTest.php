<?php
namespace Rebet\Tests\View\Engine\Blade\Compiler;

use Rebet\Config\Config;
use Rebet\Application\App;
use Rebet\Tests\RebetTestCase;
use Rebet\View\Engine\Blade\Blade;
use Rebet\View\Engine\Blade\Compiler\BladeCompiler;
use Rebet\View\Tag\CallbackProcessor;

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
        Config::application([
            Blade::class => [
                'view_path>'  => [App::path('/resources/views/blade')],
                'cache_path'  => 'vfs://root/cache',
            ],
        ]);

        $blade          = new Blade(true);
        $this->compiler = $blade->compiler();
    }

    public function test_raw()
    {
        $this->assertSame(null, $this->compiler->getCustomDirectives()['hello'] ?? null);
        $this->compiler->raw('hello', "echo('Hello');");
        $this->assertSame("<?php echo('Hello'); ?>", call_user_func($this->compiler->getCustomDirectives()['hello']));
    }

    public function test_embed()
    {
        $this->assertSame(null, $this->compiler->getCustomDirectives()['hello'] ?? null);
        $this->compiler->embed('hello', "echo(", new CallbackProcessor(function () { return 'Hello'; }), ');');
        $this->assertSame("<?php echo( \Illuminate\Support\Facades\Blade::execute('hello', []) ); ?>", call_user_func($this->compiler->getCustomDirectives()['hello'], null));

        $this->compiler->embed('say', "echo(", new CallbackProcessor(function ($word) { return $word; }), ');');
        $this->assertSame("<?php echo( \Illuminate\Support\Facades\Blade::execute('say', ['hello']) ); ?>", call_user_func($this->compiler->getCustomDirectives()['say'], "'hello'"));

        $this->compiler->embed('say', "echo(", new CallbackProcessor(function ($word) { return $word; }), ');', function ($expression) { return false; });
        $this->assertSame("<?php echo( \Illuminate\Support\Facades\Blade::execute('say', ['hello']) ); ?>\n", call_user_func($this->compiler->getCustomDirectives()['say'], "'hello'"));

        $this->compiler->embed('welcom', "echo(", new CallbackProcessor(function ($user_name, $word) { return "{$word} {$user_name}"; }), ');', null, '$user_name');
        $this->assertSame("<?php echo( \Illuminate\Support\Facades\Blade::execute('welcom', [\$user_name, 'hello']) ); ?>", call_user_func($this->compiler->getCustomDirectives()['welcom'], "'hello'"));

        $this->compiler->embed('welcom', "echo(", new CallbackProcessor(function ($user_name, $word) { return "{$word} {$user_name}"; }), ');', null, '$user_name');
        $this->assertSame("<?php echo( \Illuminate\Support\Facades\Blade::execute('welcom', [\$user_name, 'foo' => 'hello']) ); ?>", call_user_func($this->compiler->getCustomDirectives()['welcom'], "'foo' => 'hello'"));
    }

    public function test_execute()
    {
        $this->compiler->embed('say', "echo(", new CallbackProcessor(function ($word) { return $word; }), ');');
        $this->assertSame('Hello', $this->compiler->execute('say', ['Hello']));
    }

    public function test_case()
    {
        $this->compiler->case('hello', new CallbackProcessor(function ($word) { return $word === 'hello'; }));
        $directives = $this->compiler->getCustomDirectives();
        $this->assertSame("<?php if (\Illuminate\Support\Facades\Blade::execute('hello', ['welcom'])): ?>", call_user_func($directives['hello'], "'welcom'"));
        $this->assertSame("<?php elseif (\Illuminate\Support\Facades\Blade::execute('hello', ['bye'])): ?>", call_user_func($directives['elsehello'], "'bye'"));
        $this->assertSame("<?php endif; ?>", call_user_func($directives['endhello']));
        $this->assertSame("<?php if (! \Illuminate\Support\Facades\Blade::execute('hello', ['welcom'])): ?>", call_user_func($directives['hellonot'], "'welcom'"));
        $this->assertSame("<?php elseif (! \Illuminate\Support\Facades\Blade::execute('hello', ['bye'])): ?>", call_user_func($directives['elsehellonot'], "'bye'"));
        $this->assertSame(true, $this->compiler->execute('hello', ['hello']));
        $this->assertSame(false, $this->compiler->execute('hello', ['welcom']));

        $this->compiler->case('say', new CallbackProcessor(function ($word, $expect) { return $word === $expect; }), '$word');
        $directives = $this->compiler->getCustomDirectives();
        $this->assertSame("<?php if (\Illuminate\Support\Facades\Blade::execute('say', [\$word, 'welcom'])): ?>", call_user_func($directives['say'], "'welcom'"));
        $this->assertSame("<?php elseif (\Illuminate\Support\Facades\Blade::execute('say', [\$word, 'bye'])): ?>", call_user_func($directives['elsesay'], "'bye'"));
        $this->assertSame("<?php endif; ?>", call_user_func($directives['endsay']));
        $this->assertSame("<?php if (! \Illuminate\Support\Facades\Blade::execute('say', [\$word, 'welcom'])): ?>", call_user_func($directives['saynot'], "'welcom'"));
        $this->assertSame("<?php elseif (! \Illuminate\Support\Facades\Blade::execute('say', [\$word, 'bye'])): ?>", call_user_func($directives['elsesaynot'], "'bye'"));
        $this->assertSame(true, $this->compiler->execute('say', ['hello', 'hello']));
        $this->assertSame(false, $this->compiler->execute('say', ['welcom', 'hello']));

        $this->assertSame("<?php if (\Illuminate\Support\Facades\Blade::execute('say', [\$word, 'foo' => 'welcom'])): ?>", call_user_func($directives['say'], "'foo' => 'welcom'"));
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
