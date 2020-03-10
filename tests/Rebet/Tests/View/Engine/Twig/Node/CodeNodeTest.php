<?php
namespace Rebet\Tests\View\Engine\Twig\Node;

use Rebet\Tests\RebetTestCase;
use Rebet\View\Engine\Twig\Environment\Environment;
use Rebet\View\Engine\Twig\Node\CodeNode;
use Twig\Compiler;
use Twig\Lexer;
use Twig\Loader\LoaderInterface;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\NameExpression;

class CodeNodeTest extends RebetTestCase
{
    protected $env;
    protected $compiler;
    protected $lexer;

    public function setUp()
    {
        parent::setUp();
        $this->env      = new Environment($this->getMockBuilder(LoaderInterface::class)->getMock());
        $this->compiler = new Compiler($this->env);
        $this->lexer    = new Lexer($this->env);
    }

    public function test_addCallbackAndExecuteAndClear()
    {
        CodeNode::clear();
        $this->assertNull(CodeNode::execute('hello'));
        CodeNode::addCallback('hello', function ($name = 'everyone', $call = 'Hello') { return "{$call} {$name}."; });
        $this->assertSame('Hello everyone.', CodeNode::execute('hello'));
        $this->assertSame('Hello rebet.', CodeNode::execute('hello', ['rebet']));
        $this->assertSame('Good by everyone.', CodeNode::execute('hello', ['call' => 'Good by']));
        CodeNode::clear();
        $this->assertNull(CodeNode::execute('hello'));
    }

    public function test___constract()
    {
        $this->assertInstanceOf(CodeNode::class, new CodeNode('echo', 'hello', [], ';'));
    }

    public function test_compile()
    {
        $node = new CodeNode('echo', 'hello', [], ';');
        $src  = $this->compiler->compile($node)->getSource();
        $this->assertSame('echo Rebet\View\Engine\Twig\Node\CodeNode::execute("hello", []) ;', $src);

        $node = new CodeNode('echo', 'hello', [], ';', ['name']);
        $src  = $this->compiler->compile($node)->getSource();
        $this->assertSame('echo Rebet\View\Engine\Twig\Node\CodeNode::execute("hello", [0 => ($context["name"] ?? null)]) ;', $src);

        $node = new CodeNode('echo', 'hello', [], ';', ['name', 'foo']);
        $src  = $this->compiler->compile($node)->getSource();
        $this->assertSame('echo Rebet\View\Engine\Twig\Node\CodeNode::execute("hello", [0 => ($context["name"] ?? null), 1 => ($context["foo"] ?? null)]) ;', $src);

        $args = [
            new ConstantExpression('world', 0),
            new NameExpression('name', 0)
        ];
        $node = new CodeNode('echo', 'hello', $args, ';');
        $src  = $this->compiler->compile($node)->getSource();
        $this->assertSame('echo Rebet\View\Engine\Twig\Node\CodeNode::execute("hello", [0 => "world", 1 => ($context["name"] ?? null)]) ;', $src);

        $node = new CodeNode('echo', 'hello', $args, ';', ['name', 'foo']);
        $src  = $this->compiler->compile($node)->getSource();
        $this->assertSame('echo Rebet\View\Engine\Twig\Node\CodeNode::execute("hello", [0 => ($context["name"] ?? null), 1 => ($context["foo"] ?? null), 2 => "world", 3 => ($context["name"] ?? null)]) ;', $src);

        $args = [
            'foo' => new ConstantExpression('world', 0),
            'bar' => new NameExpression('name', 0)
        ];
        $node = new CodeNode('echo', 'hello', $args, ';');
        $src  = $this->compiler->compile($node)->getSource();
        $this->assertSame('echo Rebet\View\Engine\Twig\Node\CodeNode::execute("hello", ["foo" => "world", "bar" => ($context["name"] ?? null)]) ;', $src);

        $node = new CodeNode('echo', 'hello', $args, ';', ['name', 'foo']);
        $src  = $this->compiler->compile($node)->getSource();
        $this->assertSame('echo Rebet\View\Engine\Twig\Node\CodeNode::execute("hello", [0 => ($context["name"] ?? null), 1 => ($context["foo"] ?? null), "foo" => "world", "bar" => ($context["name"] ?? null)]) ;', $src);

        $args = [
            new NameExpression('status', 0)
        ];
        $node = new CodeNode('if(', 'is_active', $args, '):');
        $src  = $this->compiler->compile($node)->getSource();
        $this->assertSame('if( Rebet\View\Engine\Twig\Node\CodeNode::execute("is_active", [0 => ($context["status"] ?? null)]) ):', $src);

        $node = new CodeNode('if(', 'is_active', $args, '):', [], true);
        $src  = $this->compiler->compile($node)->getSource();
        $this->assertSame('if(!( Rebet\View\Engine\Twig\Node\CodeNode::execute("is_active", [0 => ($context["status"] ?? null)]) )):', $src);
    }
}
