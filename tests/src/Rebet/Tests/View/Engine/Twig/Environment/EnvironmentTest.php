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

    protected function setUp() : void
    {
        parent::setUp();
        $this->env = new Environment($this->getMockBuilder(LoaderInterface::class)->getMock());
    }

    public function test_raw()
    {
        $this->env->raw('hello', "echo('Hello');");
        $source = '{% hello %}';
        $expect = <<<EOS
        echo('Hello');
        EOS;
        $this->assertSame($expect, $this->renderPhpCode($source));
    }

    protected function renderPhpCode(string $source) : string
    {
        // NOTE: Parser::__construct() eagerly calls Environment::getExpressionParsers(), which finalizes
        //       (initializes) the environment's extensions. So the parser/compiler must be created here,
        //       after each test has registered its own tags via $this->env->raw()/embed()/case(), rather
        //       than upfront in setUp() which would lock out any further tag registration.
        $parser   = new Parser($this->env);
        $compiler = new Compiler($this->env);
        $stream   = $this->env->tokenize(new Source($source, ''));
        return $compiler->compile($parser->parse($stream)->getNode('body')->getNode(0))->getSource();
    }

    public function test_embed()
    {
        $this->env->embed('hello', null, [], 'echo(', new CallbackProcessor(function (string $name = 'everyone', string $greet = 'Hello') { return "{$greet} {$name}."; }), ');');

        $this->assertSame(
            <<<EOS
            // line 1
            echo( Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", []) );
            EOS,
            $this->renderPhpCode('{% hello %}')
        );

        $this->assertSame(
            <<<EOS
            // line 1
            echo( Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", ["world"]) );
            EOS,
            $this->renderPhpCode('{% hello "world" %}')
        );

        $this->assertSame(
            <<<EOS
            // line 1
            echo( Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", [(\$context["name"] ?? null)]) );
            EOS,
            $this->renderPhpCode('{% hello name %}')
        );

        $this->assertSame(
            <<<EOS
            // line 1
            echo( Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", ["greet" => "Good by"]) );
            EOS,
            $this->renderPhpCode('{% hello greet="Good by" %}')
        );
    }

    public function test_case()
    {
        $this->env->case('env', 'is', ['...' => [',', 'or']], new CallbackProcessor(function ($env) { return true; }));

        $this->assertSame(
            <<<EOS
            // line 1
            if( Rebet\View\Engine\Twig\Node\EmbedNode::execute("env", ["local"]) ) {
            // line 2
            yield "    LOCAL
            ";
            // line 3
            } elseif( Rebet\View\Engine\Twig\Node\EmbedNode::execute("elseenv", ["testing"]) ) {
            // line 4
            yield "    TESTING
            ";
            }

            EOS,
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
            if(!( Rebet\View\Engine\Twig\Node\EmbedNode::execute("env", ["local"]) )) {
            // line 2
            yield "    LOCAL
            ";
            // line 3
            } elseif(!( Rebet\View\Engine\Twig\Node\EmbedNode::execute("elseenv", ["testing"]) )) {
            // line 4
            yield "    TESTING
            ";
            } else {
            // line 6
            yield "    OTHER
            ";
            }

            EOS,
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
