<?php
namespace Rebet\Tests\View\Engine\Twig\Environment;

use Rebet\Tests\RebetTestCase;
use Rebet\View\Engine\Twig\Environment\Environment;
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

    public function test_code()
    {
        $this->env->code('hello', null, [], 'echo(', function ($name = 'everyone') { return "Hello {$anme}."; }, ');');

        $source   = '{% hello %}';
        $expect   = <<<EOS
// line 1
echo( Rebet\View\Engine\Twig\Node\CodeNode::execute("hello") );
EOS
        ;
        $stream   = $this->env->tokenize(new Source($source, ''));
        $src      = $this->compiler->compile($this->parser->parse($stream)->getNode('body')->getNode(0))->getSource();
        $this->assertSame($expect, $src);

        $source   = '{% hello "world" %}';
        $expect   = <<<EOS
// line 1
echo( Rebet\View\Engine\Twig\Node\CodeNode::execute("hello", "world") );
EOS
        ;
        $stream   = $this->env->tokenize(new Source($source, ''));
        $src      = $this->compiler->compile($this->parser->parse($stream)->getNode('body')->getNode(0))->getSource();
        $this->assertSame($expect, $src);

        $source   = '{% hello name %}';
        $expect   = <<<EOS
// line 1
echo( Rebet\View\Engine\Twig\Node\CodeNode::execute("hello", (\$context["name"] ?? null)) );
EOS
        ;
        $stream   = $this->env->tokenize(new Source($source, ''));
        $src      = $this->compiler->compile($this->parser->parse($stream)->getNode('body')->getNode(0))->getSource();
        $this->assertSame($expect, $src);
    }

    public function test_if()
    {
        $this->env->if('env', 'is', [], function ($env) { return true; });

        $source   = <<<EOS
{% env is "local" %}
    LOCAL
{% elseenv is "testing" %}
    TESTING
{% endenv %}
EOS
        ;
        $expect   = <<<EOS
// line 1
if( Rebet\View\Engine\Twig\Node\CodeNode::execute("env", "local") ) {
// line 2
echo "    LOCAL
";
// line 3
} elseif( Rebet\View\Engine\Twig\Node\CodeNode::execute("elseenv", "testing") ) {
// line 4
echo "    TESTING
";
}

EOS
        ;
        $stream   = $this->env->tokenize(new Source($source, ''));
        $src      = $this->compiler->compile($this->parser->parse($stream)->getNode('body')->getNode(0))->getSource();
        $this->assertSame($expect, $src);


        $source   = <<<EOS
{% env is not "local" %}
    LOCAL
{% elseenv is not "testing" %}
    TESTING
{% else %}
    OTHER
{% endenv %}
EOS
        ;
        $expect   = <<<EOS
// line 1
if(!( Rebet\View\Engine\Twig\Node\CodeNode::execute("env", "local") )) {
// line 2
echo "    LOCAL
";
// line 3
} elseif(!( Rebet\View\Engine\Twig\Node\CodeNode::execute("elseenv", "testing") )) {
// line 4
echo "    TESTING
";
} else {
// line 6
echo "    OTHER
";
}

EOS
        ;
        $stream   = $this->env->tokenize(new Source($source, ''));
        $src      = $this->compiler->compile($this->parser->parse($stream)->getNode('body')->getNode(0))->getSource();
        $this->assertSame($expect, $src);
    }
}
