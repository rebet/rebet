<?php
namespace Rebet\Tests\View\Engine\Twig\Environment;

use Rebet\Tests\RebetTestCase;
use Rebet\View\Engine\Twig\Environment\Environment;
use Rebet\View\Tag\CallbackProcessor;
use Twig\Compiler;
use Twig\Loader\LoaderInterface;
use Twig\Parser;
use Twig\Source;

class EnvironmentTest extends RebetTestCase
{
    /**
     * @var Environment
     */
    protected $env;
    protected $parser;
    protected $compiler;

    public function setUp()
    {
        parent::setUp();
        $this->env      = new Environment($this->getMockBuilder(LoaderInterface::class)->getMock());
        $this->parser   = new Parser($this->env);
        $this->compiler = new Compiler($this->env);
    }

    public function test_raw()
    {
        $this->env->raw('hello', "echo('Hello');");
        $source   = '{% hello %}';
        $expect   = <<<EOS
echo('Hello');
EOS
        ;
        $stream   = $this->env->tokenize(new Source($source, ''));
        $src      = $this->compiler->compile($this->parser->parse($stream)->getNode('body')->getNode(0))->getSource();
        $this->assertSame($expect, $src);
    }

    protected function renderPhpCode(string $source) : string
    {
        $stream = $this->env->tokenize(new Source($source, ''));
        return $this->compiler->compile($this->parser->parse($stream)->getNode('body')->getNode(0))->getSource();
    }

    public function test_embed()
    {
        $this->env->embed('hello', null, [], 'echo(', new CallbackProcessor(function (string $name = 'everyone', string $greet = 'Hello') { return "{$greet} {$name}."; }), ');');

        $this->assertSame(
            <<<EOS
// line 1
echo( Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", []) );
EOS
            ,
            $this->renderPhpCode('{% hello %}')
        );

        $this->assertSame(
            <<<EOS
// line 1
echo( Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", [0 => "world"]) );
EOS
            ,
            $this->renderPhpCode('{% hello "world" %}')
        );

        $this->assertSame(
            <<<EOS
// line 1
echo( Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", [0 => (\$context["name"] ?? null)]) );
EOS
            ,
            $this->renderPhpCode('{% hello name %}')
        );

        $this->assertSame(
            <<<EOS
// line 1
echo( Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", ["greet" => "Good by"]) );
EOS
            ,
            $this->renderPhpCode('{% hello greet="Good by" %}')
        );
    }

    public function test_case()
    {
        $this->env->case('env', 'is', ['...' => [',', 'or']], new CallbackProcessor(function ($env) { return true; }));

        $this->assertSame(
            <<<EOS
// line 1
if( Rebet\View\Engine\Twig\Node\EmbedNode::execute("env", [0 => "local"]) ) {
// line 2
echo "    LOCAL
";
// line 3
} elseif( Rebet\View\Engine\Twig\Node\EmbedNode::execute("elseenv", [0 => "testing"]) ) {
// line 4
echo "    TESTING
";
}

EOS
            ,
            $this->renderPhpCode(
                <<<EOS
{% env is "local" %}
    LOCAL
{% elseenv is "testing" %}
    TESTING
{% endenv %}
EOS
            )
        );

        $this->assertSame(
            <<<EOS
// line 1
if(!( Rebet\View\Engine\Twig\Node\EmbedNode::execute("env", [0 => "local"]) )) {
// line 2
echo "    LOCAL
";
// line 3
} elseif(!( Rebet\View\Engine\Twig\Node\EmbedNode::execute("elseenv", [0 => "testing"]) )) {
// line 4
echo "    TESTING
";
} else {
// line 6
echo "    OTHER
";
}

EOS
            ,
            $this->renderPhpCode(
                <<<EOS
{% env is not "local" %}
    LOCAL
{% elseenv is not "testing" %}
    TESTING
{% else %}
    OTHER
{% endenv %}
EOS
            )
        );
    }
}
