<?php
namespace Rebet\Tests\View\Engine\Twig\Node;

use Rebet\Tests\RebetTestCase;
use Rebet\View\Engine\Twig\Environment\Environment;
use Rebet\View\Engine\Twig\Node\RawNode;
use Twig\Compiler;
use Twig\Loader\LoaderInterface;

class RawNodeTest extends RebetTestCase
{
    protected $env;
    protected $compiler;

    public function setUp()
    {
        parent::setUp();
        $this->env      = new Environment($this->getMockBuilder(LoaderInterface::class)->getMock());
        $this->compiler = new Compiler($this->env);
    }

    public function test___constract()
    {
        $this->assertInstanceOf(RawNode::class, new RawNode('echo("foo");'));
    }

    public function test_compile()
    {
        $node = new RawNode('echo("foo");');
        $src  = $this->compiler->compile($node)->getSource();
        $this->assertSame('echo("foo");', $src);

        $node = new RawNode(', ');
        $src  = $this->compiler->compile($node)->getSource();
        $this->assertSame(', ', $src);
    }
}
