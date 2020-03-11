<?php
namespace Rebet\Tests\View\Engine\Twig\Parser;

use Rebet\Tests\RebetTestCase;
use Rebet\View\Engine\Twig\Environment\Environment;
use Rebet\View\Engine\Twig\Parser\EmbedTokenParser;
use Rebet\View\Tag\CallbackProcessor;
use Twig\Compiler;
use Twig\Loader\LoaderInterface;
use Twig\Parser;
use Twig\Source;
use Twig\TokenParser\TokenParserInterface;

class EmbedTokenParserTest extends RebetTestCase
{
    public function test___constract()
    {
        $this->assertInstanceOf(EmbedTokenParser::class, new EmbedTokenParser('hello', null, [], 'echo', new CallbackProcessor(function (...$args) { return "Hello dummy"; }), ';'));
    }

    public function test_getTag()
    {
        $paser = new EmbedTokenParser('hello', null, [], 'echo', new CallbackProcessor(function (...$args) { return "Hello dummy"; }), ';');
        $this->assertSame('hello', $paser->getTag());
    }

    public function dataParses()
    {
        return [
            [
                new EmbedTokenParser('hello', null, null, 'echo', new CallbackProcessor(function (...$args) { return "Hello dummy"; }), ';'),
                '{% hello %}',
                <<<EOS
// line 1
echo Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", []) ;
EOS
            ],
            [
                new EmbedTokenParser('hello', null, [], 'echo', new CallbackProcessor(function (...$args) { return "Hello dummy"; }), ';'),
                '{% hello "a" %}',
                <<<EOS
// line 1
echo Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", [0 => "a"]) ;
EOS
            ],
            [
                new EmbedTokenParser('hello', null, [','], 'echo', new CallbackProcessor(function (...$args) { return "Hello dummy"; }), ';'),
                '{% hello "a", "b" %}',
                <<<EOS
// line 1
echo Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", [0 => "a", 1 => "b"]) ;
EOS
            ],
            [
                new EmbedTokenParser('hello', null, [''], 'echo', new CallbackProcessor(function (...$args) { return "Hello dummy"; }), ';'),
                '{% hello "a" "b" %}',
                <<<EOS
// line 1
echo Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", [0 => "a", 1 => "b"]) ;
EOS
            ],
            [
                new EmbedTokenParser('hello', null, ['*' => ','], 'echo', new CallbackProcessor(function (...$args) { return "Hello dummy"; }), ';'),
                '{% hello "world" %}',
                <<<EOS
// line 1
echo Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", [0 => "world"]) ;
EOS
            ],
            [
                new EmbedTokenParser('hello', null, ['*' => ','], 'echo', new CallbackProcessor(function (...$args) { return "Hello dummy"; }), ';'),
                '{% hello name %}',
                <<<EOS
// line 1
echo Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", [0 => (\$context["name"] ?? null)]) ;
EOS
            ],
            [
                new EmbedTokenParser('hello', null, ['*' => ','], 'echo', new CallbackProcessor(function (...$args) { return "Hello dummy"; }), ';'),
                '{% hello name, "!" %}',
                <<<EOS
// line 1
echo Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", [0 => (\$context["name"] ?? null), 1 => "!"]) ;
EOS
            ],
            [
                new EmbedTokenParser('hello', null, ['*' => [',', 'and']], 'echo', new CallbackProcessor(function (...$args) { return "Hello dummy"; }), ';'),
                '{% hello you, he and name %}',
                <<<EOS
// line 1
echo Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", [0 => (\$context["you"] ?? null), 1 => (\$context["he"] ?? null), 2 => (\$context["name"] ?? null)]) ;
EOS
            ],
            [
                new EmbedTokenParser('hello', null, ['*' => ','], 'echo', new CallbackProcessor(function (...$args) { return "Hello dummy"; }), ';'),
                '{% hello [you, he, name], "!" %}',
                <<<EOS
// line 1
echo Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", [0 => [0 => (\$context["you"] ?? null), 1 => (\$context["he"] ?? null), 2 => (\$context["name"] ?? null)], 1 => "!"]) ;
EOS
            ],
            [
                new EmbedTokenParser('hello', null, ['*' => ','], 'echo', new CallbackProcessor(function (...$args) { return "Hello dummy"; }), ';', ['foo']),
                '{% hello %}',
                <<<EOS
// line 1
echo Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", [0 => (\$context["foo"] ?? null)]) ;
EOS
            ],
            [
                new EmbedTokenParser('hello', null, ['*' => ','], 'echo', new CallbackProcessor(function (...$args) { return "Hello dummy"; }), ';', ['foo']),
                '{% hello "world" %}',
                <<<EOS
// line 1
echo Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", [0 => (\$context["foo"] ?? null), 1 => "world"]) ;
EOS
            ],
            [
                new EmbedTokenParser('hello', null, ['*' => ','], 'echo', new CallbackProcessor(function (...$args) { return "Hello dummy"; }), ';', ['foo']),
                '{% hello "world", bar %}',
                <<<EOS
// line 1
echo Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", [0 => (\$context["foo"] ?? null), 1 => "world", 2 => (\$context["bar"] ?? null)]) ;
EOS
            ],
            [
                new EmbedTokenParser('role', 'is', ['*' => ','], 'if(', new CallbackProcessor(function ($role) { return true; }), ") {\n"),
                '{% role is "admin" %}',
                <<<EOS
// line 1
if( Rebet\View\Engine\Twig\Node\EmbedNode::execute("role", [0 => "admin"]) ) {

EOS
            ],
            [
                new EmbedTokenParser('role', 'is', ['*' => ','], 'if(', new CallbackProcessor(function ($role) { return true; }), ") {\n"),
                '{% role is "admin", "user"%}',
                <<<EOS
// line 1
if( Rebet\View\Engine\Twig\Node\EmbedNode::execute("role", [0 => "admin", 1 => "user"]) ) {

EOS
            ],
            [
                new EmbedTokenParser('role', 'is', ['*' => [',', 'or']], 'if(', new CallbackProcessor(function ($role) { return true; }), ") {\n"),
                '{% role is "admin", "user"%}',
                <<<EOS
// line 1
if( Rebet\View\Engine\Twig\Node\EmbedNode::execute("role", [0 => "admin", 1 => "user"]) ) {

EOS
            ],
            [
                new EmbedTokenParser('role', 'is', ['*' => [',', 'or']], 'if(', new CallbackProcessor(function ($role) { return true; }), ") {\n"),
                '{% role is "admin" or "user"%}',
                <<<EOS
// line 1
if( Rebet\View\Engine\Twig\Node\EmbedNode::execute("role", [0 => "admin", 1 => "user"]) ) {

EOS
            ],
            [
                new EmbedTokenParser('role', 'is', ['*' => ','], 'if(', new CallbackProcessor(function ($role) { return true; }), ") {\n"),
                '{% role is not "admin" %}',
                <<<EOS
// line 1
if(!( Rebet\View\Engine\Twig\Node\EmbedNode::execute("role", [0 => "admin"]) )) {

EOS
            ],
            [
                new EmbedTokenParser('role', 'in', ['*' => ','], 'if(', new CallbackProcessor(function ($role) { return true; }), ") {\n"),
                '{% role in "admin", "user" %}',
                <<<EOS
// line 1
if( Rebet\View\Engine\Twig\Node\EmbedNode::execute("role", [0 => "admin", 1 => "user"]) ) {

EOS
            ],
            [
                new EmbedTokenParser('role', 'in', ['*' => ','], 'if(', new CallbackProcessor(function ($role) { return true; }), ") {\n"),
                '{% role not in "admin", "user" %}',
                <<<EOS
// line 1
if(!( Rebet\View\Engine\Twig\Node\EmbedNode::execute("role", [0 => "admin", 1 => "user"]) )) {

EOS
            ],
            [
                new EmbedTokenParser('role', 'is', ['*' => [',', 'or']], 'if(', new CallbackProcessor(function ($role) { return true; }), ") {\n"),
                '{% role is "a", "b", "c", "d" or "e" %}',
                <<<EOS
// line 1
if( Rebet\View\Engine\Twig\Node\EmbedNode::execute("role", [0 => "a", 1 => "b", 2 => "c", 3 => "d", 4 => "e"]) ) {

EOS
            ],
            [
                new EmbedTokenParser('role', 'is', ['*' => [',', 'or']], 'if(', new CallbackProcessor(function ($role) { return true; }), ") {\n"),
                '{% role is "a", "b", "c", ("d" or "e") %}',
                <<<EOS
// line 1
if( Rebet\View\Engine\Twig\Node\EmbedNode::execute("role", [0 => "a", 1 => "b", 2 => "c", 3 => ("d" || "e")]) ) {

EOS
            ],
            [
                new EmbedTokenParser('role', 'is', ['or', ':', '*' => ','], 'if(', new CallbackProcessor(function ($role) { return true; }), ") {\n"),
                '{% role is "a" or "b" : "c", "d", "e" %}',
                <<<EOS
// line 1
if( Rebet\View\Engine\Twig\Node\EmbedNode::execute("role", [0 => "a", 1 => "b", 2 => "c", 3 => "d", 4 => "e"]) ) {

EOS
            ],
            [
                new EmbedTokenParser('role', 'is', ['with', '*' => [',', 'and']], 'if(', new CallbackProcessor(function ($role) { return true; }), ") {\n"),
                '{% role is "a" with "b", "c", "d" and "e" %}',
                <<<EOS
// line 1
if( Rebet\View\Engine\Twig\Node\EmbedNode::execute("role", [0 => "a", 1 => "b", 2 => "c", 3 => "d", 4 => "e"]) ) {

EOS
            ],
            [
                new EmbedTokenParser('role', 'is', ['with', '*' => [',', 'and']], 'if(', new CallbackProcessor(function ($role) { return true; }), ") {\n"),
                '{% role is "a" with "b", "c", "d", "e" %}',
                <<<EOS
// line 1
if( Rebet\View\Engine\Twig\Node\EmbedNode::execute("role", [0 => "a", 1 => "b", 2 => "c", 3 => "d", 4 => "e"]) ) {

EOS
            ],
            [
                new EmbedTokenParser('can', '', [], 'if(', new CallbackProcessor(function ($action) { return true; }), ") {\n"),
                '{% can "update" %}',
                <<<EOS
// line 1
if( Rebet\View\Engine\Twig\Node\EmbedNode::execute("can", [0 => "update"]) ) {

EOS
            ],
            [
                new EmbedTokenParser('can', '', [], 'if(', new CallbackProcessor(function ($action) { return true; }), ") {\n"),
                '{% can not "update" %}',
                <<<EOS
// line 1
if(!( Rebet\View\Engine\Twig\Node\EmbedNode::execute("can", [0 => "update"]) )) {

EOS
            ],
            [
                new EmbedTokenParser('can', null, [], 'if(', new CallbackProcessor(function ($action) { return true; }), ") {\n"),
                '{% can not "update" %}',
                <<<EOS
// line 1
if(!( Rebet\View\Engine\Twig\Node\EmbedNode::execute("can", [0 => "update"]) )) {

EOS
            ],
            [
                new EmbedTokenParser('hello', null, ['??'], 'echo', new CallbackProcessor(function (...$args) { return "Hello dummy"; }), ';'),
                '{% hello "world" ?? "default" %}',
                <<<EOS
// line 1
echo Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", [0 => "world", 1 => "default"]) ;
EOS
            ],
            [
                new EmbedTokenParser('hello', null, ['??'], 'echo', new CallbackProcessor(function (...$args) { return "Hello dummy"; }), ';', [], true),
                '{% hello ?? "default" %}',
                <<<EOS
// line 1
echo Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", [0 => "default"]) ;
EOS
            ],
        ];
    }

    /**
     * @dataProvider dataParses
     */
    public function test_parse(TokenParserInterface $parser, string $source, string $expect)
    {
        $this->assertSame($expect, $this->renderPhpCode($parser, $source));
    }

    /**
     * @expectedException Twig\Error\SyntaxError
     * @expectedExceptionMessage Too many code arguments. The code tag 'hello' takes no arguments at line 1.
     */
    public function test_parse_faile_empty()
    {
        $this->renderPhpCode(
            new EmbedTokenParser('hello', null, null, 'echo', new CallbackProcessor(function (...$args) { return "Hello dummy"; }), ';'),
            '{% hello "a" %}'
        );
    }

    /**
     * @expectedException Twig\Error\SyntaxError
     * @expectedExceptionMessage Too many code arguments. The code tag 'hello' takes only one argument at line 1.
     */
    public function test_parse_faile_one()
    {
        $this->renderPhpCode(
            new EmbedTokenParser('hello', null, [], 'echo', new CallbackProcessor(function (...$args) { return "Hello dummy"; }), ';'),
            '{% hello "a" "b" %}'
        );
    }

    /**
     * @expectedException Twig\Error\SyntaxError
     * @expectedExceptionMessage 1st and 2nd arguments of the code tag 'hello' must be separated by 'with' at line 1.
     */
    public function test_parse_faile_1st()
    {
        $this->renderPhpCode(
            new EmbedTokenParser('hello', null, ['with'], 'echo', new CallbackProcessor(function (...$args) { return "Hello dummy"; }), ';'),
            '{% hello "a", "b" %}'
        );
    }

    /**
     * @expectedException Twig\Error\SyntaxError
     * @expectedExceptionMessage 1st and 2nd arguments of the code tag 'hello' must be separated by ',' or 'or' at line 1.
     */
    public function test_parse_faile_1st2()
    {
        $this->renderPhpCode(
            new EmbedTokenParser('hello', null, [[',', 'or']], 'echo', new CallbackProcessor(function (...$args) { return "Hello dummy"; }), ';'),
            '{% hello "a" "b" %}'
        );
    }

    /**
     * @expectedException Twig\Error\SyntaxError
     * @expectedExceptionMessage Too many code arguments. The code tag 'hello' takes up to 2 arguments at line 1.
     */
    public function test_parse_faile_2nd()
    {
        $this->renderPhpCode(
            new EmbedTokenParser('hello', null, [','], 'echo', new CallbackProcessor(function (...$args) { return "Hello dummy"; }), ';'),
            '{% hello "a", "b", "c" %}'
        );
    }

    protected function renderPhpCode(TokenParserInterface $parser, string $source) : string
    {
        $env      = new Environment($this->getMockBuilder(LoaderInterface::class)->getMock());
        $env->addTokenParser($parser);
        $stream   = $env->tokenize(new Source($source, ''));
        $parser   = new Parser($env);
        $compiler = new Compiler($env);
        return $compiler->compile($parser->parse($stream)->getNode('body')->getNode(0))->getSource();
    }
}
