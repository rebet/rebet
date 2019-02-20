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
        $this->assertInstanceOf(CodeTokenParser::class, new CodeTokenParser('hello', null, 'echo', function (...$args) { return "Hello dummy"; }, ';'));
    }

    public function test_getTag()
    {
        $paser = new CodeTokenParser('hello', null, 'echo', function (...$args) { return "Hello dummy"; }, ';');
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
                new CodeTokenParser('hello', null, 'echo', function (...$args) { return "Hello dummy"; }, ';'),
                '{% hello %}',
                <<<EOS
// line 1
echo Rebet\View\Engine\Twig\Node\CodeNode::execute("hello") ;
EOS
            ],
            [
                new CodeTokenParser('hello', null, 'echo', function (...$args) { return "Hello dummy"; }, ';'),
                '{% hello [] %}',
                <<<EOS
// line 1
echo Rebet\View\Engine\Twig\Node\CodeNode::execute("hello", []) ;
EOS
            ],
            [
                new CodeTokenParser('hello', null, 'echo', function (...$args) { return "Hello dummy"; }, ';'),
                '{% hello "world" %}',
                <<<EOS
// line 1
echo Rebet\View\Engine\Twig\Node\CodeNode::execute("hello", "world") ;
EOS
            ],
            [
                new CodeTokenParser('hello', null, 'echo', function (...$args) { return "Hello dummy"; }, ';'),
                '{% hello name %}',
                <<<EOS
// line 1
echo Rebet\View\Engine\Twig\Node\CodeNode::execute("hello", (\$context["name"] ?? null)) ;
EOS
            ],
            [
                new CodeTokenParser('hello', null, 'echo', function (...$args) { return "Hello dummy"; }, ';'),
                '{% hello name "!" %}',
                <<<EOS
// line 1
echo Rebet\View\Engine\Twig\Node\CodeNode::execute("hello", (\$context["name"] ?? null), "!") ;
EOS
            ],
            [
                new CodeTokenParser('hello', null, 'echo', function (...$args) { return "Hello dummy"; }, ';'),
                '{% hello name "!" %}',
                <<<EOS
// line 1
echo Rebet\View\Engine\Twig\Node\CodeNode::execute("hello", (\$context["name"] ?? null), "!") ;
EOS
            ],
            [
                new CodeTokenParser('hello', null, 'echo', function (...$args) { return "Hello dummy"; }, ';'),
                '{% hello you, he and name "!" %}',
                <<<EOS
// line 1
echo Rebet\View\Engine\Twig\Node\CodeNode::execute("hello", (\$context["you"] ?? null), (\$context["he"] ?? null), (\$context["name"] ?? null), "!") ;
EOS
            ],
            [
                new CodeTokenParser('hello', null, 'echo', function (...$args) { return "Hello dummy"; }, ';'),
                '{% hello [you, he, name] "!" %}',
                <<<EOS
// line 1
echo Rebet\View\Engine\Twig\Node\CodeNode::execute("hello", [0 => (\$context["you"] ?? null), 1 => (\$context["he"] ?? null), 2 => (\$context["name"] ?? null)], "!") ;
EOS
            ],
            [
                new CodeTokenParser('hello', null, 'echo', function (...$args) { return "Hello dummy"; }, ';', ['foo']),
                '{% hello %}',
                <<<EOS
// line 1
echo Rebet\View\Engine\Twig\Node\CodeNode::execute("hello", (\$context["foo"] ?? null)) ;
EOS
            ],
            [
                new CodeTokenParser('hello', null, 'echo', function (...$args) { return "Hello dummy"; }, ';', ['foo']),
                '{% hello "world" %}',
                <<<EOS
// line 1
echo Rebet\View\Engine\Twig\Node\CodeNode::execute("hello", (\$context["foo"] ?? null), "world") ;
EOS
            ],
            [
                new CodeTokenParser('hello', null, 'echo', function (...$args) { return "Hello dummy"; }, ';', ['foo']),
                '{% hello "world" bar %}',
                <<<EOS
// line 1
echo Rebet\View\Engine\Twig\Node\CodeNode::execute("hello", (\$context["foo"] ?? null), "world", (\$context["bar"] ?? null)) ;
EOS
            ],
            [
                new CodeTokenParser('role', 'is', 'if(', function ($role) { return true; }, ") {\n"),
                '{% role is "admin" %}',
                <<<EOS
// line 1
if( Rebet\View\Engine\Twig\Node\CodeNode::execute("role", "admin") ) {

EOS
            ],
            [
                new CodeTokenParser('role', 'is', 'if(', function ($role) { return true; }, ") {\n"),
                '{% role is "admin" "user"%}',
                <<<EOS
// line 1
if( Rebet\View\Engine\Twig\Node\CodeNode::execute("role", "admin", "user") ) {

EOS
            ],
            [
                new CodeTokenParser('role', 'is', 'if(', function ($role) { return true; }, ") {\n"),
                '{% role is "admin", "user"%}',
                <<<EOS
// line 1
if( Rebet\View\Engine\Twig\Node\CodeNode::execute("role", "admin", "user") ) {

EOS
            ],
            [
                new CodeTokenParser('role', 'is', 'if(', function ($role) { return true; }, ") {\n"),
                '{% role is "admin" or "user"%}',
                <<<EOS
// line 1
if( Rebet\View\Engine\Twig\Node\CodeNode::execute("role", "admin", "user") ) {

EOS
            ],
            [
                new CodeTokenParser('role', 'is', 'if(', function ($role) { return true; }, ") {\n"),
                '{% role is not "admin" %}',
                <<<EOS
// line 1
if(!( Rebet\View\Engine\Twig\Node\CodeNode::execute("role", "admin") )) {

EOS
            ],
            [
                new CodeTokenParser('role', 'in', 'if(', function ($role) { return true; }, ") {\n"),
                '{% role in "admin", "user" %}',
                <<<EOS
// line 1
if( Rebet\View\Engine\Twig\Node\CodeNode::execute("role", "admin", "user") ) {

EOS
            ],
            [
                new CodeTokenParser('role', 'in', 'if(', function ($role) { return true; }, ") {\n"),
                '{% role not in "admin", "user" %}',
                <<<EOS
// line 1
if(!( Rebet\View\Engine\Twig\Node\CodeNode::execute("role", "admin", "user") )) {

EOS
            ],
        ];
    }
}
