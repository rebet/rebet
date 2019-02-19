<?php
namespace Rebet\Tests\View\Engine\Twig\Parser;

use Rebet\Tests\RebetTestCase;
use Rebet\View\Engine\Twig\Environment\Environment;
use Rebet\View\Engine\Twig\Parser\CodeTokenParser;
use Twig\Compiler;
use Twig\Loader\LoaderInterface;
use Twig\Parser;
use Twig\Source;
use Twig\TokenParser\TokenParserInterface;

class CodeTokenParserTest extends RebetTestCase
{
    public function test___constract()
    {
        $this->assertInstanceOf(CodeTokenParser::class, new CodeTokenParser('hello', 'echo', function ($name = 'everyone') { return "Hello {$name}."; }, ';'));
    }

    public function test_getTag()
    {
        $paser = new CodeTokenParser('hello', 'echo', function ($name = 'everyone') { return "Hello {$name}."; }, ';');
        $this->assertSame('hello', $paser->getTag());
    }

    /**
     * @dataProvider dataParses
     */
    public function test_parse(TokenParserInterface $token_parser, string $source, string $expect)
    {
        $env = new Environment($this->getMockBuilder(LoaderInterface::class)->getMock());
        $env->addTokenParser($token_parser);
        $stream   = $env->tokenize(new Source($source, ''));
        $parser   = new Parser($env);
        $compiler = new Compiler($env);
        $src      = $compiler->compile($parser->parse($stream)->getNode('body')->getNode(0))->getSource();

        $this->assertSame($expect, $src);
    }

    public function dataParses()
    {
        return [
            [
                new CodeTokenParser('hello', 'echo', function ($name = 'everyone') { return "Hello {$name}."; }, ';'),
                '{% hello %}',
                <<<EOS
// line 1
echo Rebet\View\Engine\Twig\Node\CodeNode::execute("hello") ;
EOS
            ],
            [
                new CodeTokenParser('hello', 'echo', function ($name = 'everyone') { return "Hello {$name}."; }, ';'),
                '{% hello() %}',
                <<<EOS
// line 1
echo Rebet\View\Engine\Twig\Node\CodeNode::execute("hello") ;
EOS
            ],
            [
                new CodeTokenParser('hello', 'echo', function ($name = 'everyone') { return "Hello {$name}."; }, ';'),
                '{% hello("world") %}',
                <<<EOS
// line 1
echo Rebet\View\Engine\Twig\Node\CodeNode::execute("hello", "world") ;
EOS
            ],
            [
                new CodeTokenParser('hello', 'echo', function ($name = 'everyone') { return "Hello {$name}."; }, ';'),
                '{% hello(name) %}',
                <<<EOS
// line 1
echo Rebet\View\Engine\Twig\Node\CodeNode::execute("hello", (\$context["name"] ?? null)) ;
EOS
            ],
            [
                new CodeTokenParser('hello', 'echo', function ($name = 'everyone') { return "Hello {$name}."; }, ';'),
                '{% hello("foo", name) %}',
                <<<EOS
// line 1
echo Rebet\View\Engine\Twig\Node\CodeNode::execute("hello", "foo", (\$context["name"] ?? null)) ;
EOS
            ],
            [
                new CodeTokenParser('hello', 'echo', function ($name = 'everyone') { return "Hello {$name}."; }, ';', ['foo']),
                '{% hello() %}',
                <<<EOS
// line 1
echo Rebet\View\Engine\Twig\Node\CodeNode::execute("hello", (\$context["foo"] ?? null)) ;
EOS
            ],
            [
                new CodeTokenParser('hello', 'echo', function ($name = 'everyone') { return "Hello {$name}."; }, ';', ['foo']),
                '{% hello("world") %}',
                <<<EOS
// line 1
echo Rebet\View\Engine\Twig\Node\CodeNode::execute("hello", (\$context["foo"] ?? null), "world") ;
EOS
            ],
            [
                new CodeTokenParser('hello', 'echo', function ($name = 'everyone') { return "Hello {$name}."; }, ';', ['foo']),
                '{% hello("world", bar) %}',
                <<<EOS
// line 1
echo Rebet\View\Engine\Twig\Node\CodeNode::execute("hello", (\$context["foo"] ?? null), "world", (\$context["bar"] ?? null)) ;
EOS
            ],
            [
                new CodeTokenParser('is_active', 'if(', function ($status) { return $status === 1; }, ") {\n"),
                '{% is_active(user_status) %}',
                <<<EOS
// line 1
if( Rebet\View\Engine\Twig\Node\CodeNode::execute("is_active", (\$context["user_status"] ?? null)) ) {

EOS
            ],
        ];
    }
}
