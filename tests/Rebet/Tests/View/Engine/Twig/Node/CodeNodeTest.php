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
use Twig\Node\Node;

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
        CodeNode::addCallback('hello', function ($name = 'everyone') { return "Hello {$name}."; });
        $this->assertSame('Hello everyone.', CodeNode::execute('hello'));
        $this->assertSame('Hello rebet.', CodeNode::execute('hello', 'rebet'));
        CodeNode::clear();
        $this->assertNull(CodeNode::execute('hello'));
    }

    public function test___constract()
    {
        $this->assertInstanceOf(CodeNode::class, new CodeNode('echo', 'hello', new Node([]), ';'));
    }

    public function test_compile()
    {
        $node = new CodeNode('echo', 'hello', new Node([]), ';');
        $src  = $this->compiler->compile($node)->getSource();
        $this->assertSame('echo Rebet\View\Engine\Twig\Node\CodeNode::execute("hello") ;', $src);

        $node = new CodeNode('echo', 'hello', new Node([]), ';', ['name']);
        $src  = $this->compiler->compile($node)->getSource();
        $this->assertSame('echo Rebet\View\Engine\Twig\Node\CodeNode::execute("hello", ($context["name"] ?? null)) ;', $src);

        $node = new CodeNode('echo', 'hello', new Node([]), ';', ['name', 'foo']);
        $src  = $this->compiler->compile($node)->getSource();
        $this->assertSame('echo Rebet\View\Engine\Twig\Node\CodeNode::execute("hello", ($context["name"] ?? null), ($context["foo"] ?? null)) ;', $src);

        $args = new Node([
            new ConstantExpression('world', 0),
            new NameExpression('name', 0)
        ]);
        $node = new CodeNode('echo', 'hello', $args, ';');
        $src  = $this->compiler->compile($node)->getSource();
        $this->assertSame('echo Rebet\View\Engine\Twig\Node\CodeNode::execute("hello", "world", ($context["name"] ?? null)) ;', $src);

        $node = new CodeNode('echo', 'hello', $args, ';', ['name', 'foo']);
        $src  = $this->compiler->compile($node)->getSource();
        $this->assertSame('echo Rebet\View\Engine\Twig\Node\CodeNode::execute("hello", ($context["name"] ?? null), ($context["foo"] ?? null), "world", ($context["name"] ?? null)) ;', $src);

        $args = new Node([
            new NameExpression('status', 0)
        ]);
        $node = new CodeNode('if(', 'is_active', $args, '):');
        $src  = $this->compiler->compile($node)->getSource();
        $this->assertSame('if( Rebet\View\Engine\Twig\Node\CodeNode::execute("is_active", ($context["status"] ?? null)) ):', $src);

        $node = new CodeNode('if(', 'is_active', $args, '):', [], true);
        $src  = $this->compiler->compile($node)->getSource();
        $this->assertSame('if(!( Rebet\View\Engine\Twig\Node\CodeNode::execute("is_active", ($context["status"] ?? null)) )):', $src);
    }
}
