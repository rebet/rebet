<?php
namespace Rebet\Tests\View\Engine\Twig\Node;

use Rebet\Tests\RebetTestCase;
use Rebet\View\Engine\Twig\Environment\Environment;
use Rebet\View\Engine\Twig\Node\EmbedNode;
use Rebet\View\Tag\CallbackProcessor;
use Twig\Compiler;
use Twig\Lexer;
use Twig\Loader\LoaderInterface;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\NameExpression;

class EmbedNodeTest extends RebetTestCase
{
    protected $env;
    protected $compiler;
    protected $lexer;

    protected function setUp() : void
    {
        parent::setUp();
        $this->env      = new Environment($this->getMockBuilder(LoaderInterface::class)->getMock());
        $this->compiler = new Compiler($this->env);
        $this->lexer    = new Lexer($this->env);
    }

    public function test_addCallbackAndExecuteAndClear()
    {
        EmbedNode::reset();
        $this->assertNull(EmbedNode::execute('hello'));
        EmbedNode::addCode('hello', new CallbackProcessor(function ($name = 'everyone', $call = 'Hello') { return "{$call} {$name}."; }));
        $this->assertSame('Hello everyone.', EmbedNode::execute('hello'));
        $this->assertSame('Hello rebet.', EmbedNode::execute('hello', ['rebet']));
        $this->assertSame('Good by everyone.', EmbedNode::execute('hello', ['call' => 'Good by']));
        EmbedNode::reset();
        $this->assertNull(EmbedNode::execute('hello'));
    }

    public function test___constract()
    {
        $this->assertInstanceOf(EmbedNode::class, new EmbedNode('echo', 'hello', [], ';'));
    }

    public function test_compile()
    {
        $node = new EmbedNode('echo', 'hello', [], ';');
        $src  = $this->compiler->compile($node)->getSource();
        $this->assertSame('echo Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", []) ;', $src);

        $node = new EmbedNode('echo', 'hello', [], ';', ['name']);
        $src  = $this->compiler->compile($node)->getSource();
        $this->assertSame('echo Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", [($context["name"] ?? null)]) ;', $src);

        $node = new EmbedNode('echo', 'hello', [], ';', ['name', 'foo']);
        $src  = $this->compiler->compile($node)->getSource();
        $this->assertSame('echo Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", [($context["name"] ?? null), ($context["foo"] ?? null)]) ;', $src);

        $args = [
            new ConstantExpression('world', 0),
            new NameExpression('name', 0)
        ];
        $node = new EmbedNode('echo', 'hello', $args, ';');
        $src  = $this->compiler->compile($node)->getSource();
        $this->assertSame('echo Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", ["world", ($context["name"] ?? null)]) ;', $src);

        $node = new EmbedNode('echo', 'hello', $args, ';', ['name', 'foo']);
        $src  = $this->compiler->compile($node)->getSource();
        $this->assertSame('echo Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", [($context["name"] ?? null), ($context["foo"] ?? null), "world", ($context["name"] ?? null)]) ;', $src);

        $args = [
            'foo' => new ConstantExpression('world', 0),
            'bar' => new NameExpression('name', 0)
        ];
        $node = new EmbedNode('echo', 'hello', $args, ';');
        $src  = $this->compiler->compile($node)->getSource();
        $this->assertSame('echo Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", ["foo" => "world", "bar" => ($context["name"] ?? null)]) ;', $src);

        $node = new EmbedNode('echo', 'hello', $args, ';', ['name', 'foo']);
        $src  = $this->compiler->compile($node)->getSource();
        $this->assertSame('echo Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", [($context["name"] ?? null), ($context["foo"] ?? null), "foo" => "world", "bar" => ($context["name"] ?? null)]) ;', $src);

        $args = [
            new NameExpression('status', 0)
        ];
        $node = new EmbedNode('if(', 'is_active', $args, '):');
        $src  = $this->compiler->compile($node)->getSource();
        $this->assertSame('if( Rebet\View\Engine\Twig\Node\EmbedNode::execute("is_active", [($context["status"] ?? null)]) ):', $src);

        $node = new EmbedNode('if(', 'is_active', $args, '):', [], true);
        $src  = $this->compiler->compile($node)->getSource();
        $this->assertSame('if(!( Rebet\View\Engine\Twig\Node\EmbedNode::execute("is_active", [($context["status"] ?? null)]) )):', $src);
    }
}
