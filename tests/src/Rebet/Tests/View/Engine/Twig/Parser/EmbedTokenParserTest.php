<?php

declare(strict_types=1);

namespace Rebet\Tests\View\Engine\Twig\Parser;

use PHPUnit\Framework\Attributes\DataProvider;
use Rebet\Tests\RebetTestCase;
use Rebet\View\Engine\Twig\Environment\Environment;
use Rebet\View\Engine\Twig\Parser\EmbedTokenParser;
use Rebet\View\Tag\CallbackProcessor;
use Twig\Compiler;
use Twig\Error\SyntaxError;
use Twig\Loader\LoaderInterface;
use Twig\Parser;
use Twig\Source;
use Twig\TokenParser\TokenParserInterface;

class EmbedTokenParserTest extends RebetTestCase
{
    public function test___constract(): void
    {
        $this->assertInstanceOf(EmbedTokenParser::class, new EmbedTokenParser('hello', null, [], 'echo', new CallbackProcessor(fn(...$args) => "Hello dummy"), ';'));
    }

    public function test_getTag(): void
    {
        $paser = new EmbedTokenParser('hello', null, [], 'echo', new CallbackProcessor(fn(...$args) => "Hello dummy"), ';');
        $this->assertSame('hello', $paser->getTag());
    }

    public static function dataParses()
    {
        return [
            [
                new EmbedTokenParser('hello', null, null, 'echo', new CallbackProcessor(fn(...$args) => "Hello dummy"), ';'),
                '{% hello %}',
                <<<EOS
                    // line 1
                    echo Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", []) ;
                    EOS,
            ],
            [
                new EmbedTokenParser('hello', null, [], 'echo', new CallbackProcessor(fn(...$args) => "Hello dummy"), ';'),
                '{% hello "a" %}',
                <<<EOS
                    // line 1
                    echo Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", ["a"]) ;
                    EOS,
            ],
            [
                new EmbedTokenParser('hello', null, [','], 'echo', new CallbackProcessor(fn(...$args) => "Hello dummy"), ';'),
                '{% hello "a", "b" %}',
                <<<EOS
                    // line 1
                    echo Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", ["a", "b"]) ;
                    EOS,
            ],
            [
                new EmbedTokenParser('hello', null, [''], 'echo', new CallbackProcessor(fn(...$args) => "Hello dummy"), ';'),
                '{% hello "a" "b" %}',
                <<<EOS
                    // line 1
                    echo Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", ["a", "b"]) ;
                    EOS,
            ],
            [
                new EmbedTokenParser('hello', null, ['...' => ','], 'echo', new CallbackProcessor(fn(...$args) => "Hello dummy"), ';'),
                '{% hello "world" %}',
                <<<EOS
                    // line 1
                    echo Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", ["world"]) ;
                    EOS,
            ],
            [
                new EmbedTokenParser('hello', null, ['...' => ','], 'echo', new CallbackProcessor(fn(...$args) => "Hello dummy"), ';'),
                '{% hello name %}',
                <<<EOS
                    // line 1
                    echo Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", [(\$context["name"] ?? null)]) ;
                    EOS,
            ],
            [
                new EmbedTokenParser('hello', null, ['...' => ','], 'echo', new CallbackProcessor(fn(...$args) => "Hello dummy"), ';'),
                '{% hello name, "!" %}',
                <<<EOS
                    // line 1
                    echo Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", [(\$context["name"] ?? null), "!"]) ;
                    EOS,
            ],
            [
                new EmbedTokenParser('hello', null, ['...' => [',', 'and']], 'echo', new CallbackProcessor(fn(...$args) => "Hello dummy"), ';'),
                '{% hello you, he and name %}',
                <<<EOS
                    // line 1
                    echo Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", [(\$context["you"] ?? null), (\$context["he"] ?? null), (\$context["name"] ?? null)]) ;
                    EOS,
            ],
            [
                new EmbedTokenParser('hello', null, ['...' => ','], 'echo', new CallbackProcessor(fn(...$args) => "Hello dummy"), ';'),
                '{% hello [you, he, name], "!" %}',
                <<<EOS
                    // line 1
                    echo Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", [[(\$context["you"] ?? null), (\$context["he"] ?? null), (\$context["name"] ?? null)], "!"]) ;
                    EOS,
            ],
            [
                new EmbedTokenParser('hello', null, ['...' => ','], 'echo', new CallbackProcessor(fn(...$args) => "Hello dummy"), ';', ['foo']),
                '{% hello %}',
                <<<EOS
                    // line 1
                    echo Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", [(\$context["foo"] ?? null)]) ;
                    EOS,
            ],
            [
                new EmbedTokenParser('hello', null, ['...' => ','], 'echo', new CallbackProcessor(fn(...$args) => "Hello dummy"), ';', ['foo']),
                '{% hello "world" %}',
                <<<EOS
                    // line 1
                    echo Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", [(\$context["foo"] ?? null), "world"]) ;
                    EOS,
            ],
            [
                new EmbedTokenParser('hello', null, ['...' => ','], 'echo', new CallbackProcessor(fn(...$args) => "Hello dummy"), ';', ['foo']),
                '{% hello "world", bar %}',
                <<<EOS
                    // line 1
                    echo Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", [(\$context["foo"] ?? null), "world", (\$context["bar"] ?? null)]) ;
                    EOS,
            ],
            [
                new EmbedTokenParser('role', 'is', ['...' => ','], 'if(', new CallbackProcessor(fn($role) => true), ") {\n"),
                '{% role is "admin" %}',
                <<<EOS
                    // line 1
                    if( Rebet\View\Engine\Twig\Node\EmbedNode::execute("role", ["admin"]) ) {

                    EOS,
            ],
            [
                new EmbedTokenParser('role', 'is', ['...' => ','], 'if(', new CallbackProcessor(fn($role) => true), ") {\n"),
                '{% role is "admin", "user"%}',
                <<<EOS
                    // line 1
                    if( Rebet\View\Engine\Twig\Node\EmbedNode::execute("role", ["admin", "user"]) ) {

                    EOS,
            ],
            [
                new EmbedTokenParser('role', 'is', ['...' => [',', 'or']], 'if(', new CallbackProcessor(fn($role) => true), ") {\n"),
                '{% role is "admin", "user"%}',
                <<<EOS
                    // line 1
                    if( Rebet\View\Engine\Twig\Node\EmbedNode::execute("role", ["admin", "user"]) ) {

                    EOS,
            ],
            [
                new EmbedTokenParser('role', 'is', ['...' => [',', 'or']], 'if(', new CallbackProcessor(fn($role) => true), ") {\n"),
                '{% role is "admin" or "user"%}',
                <<<EOS
                    // line 1
                    if( Rebet\View\Engine\Twig\Node\EmbedNode::execute("role", ["admin", "user"]) ) {

                    EOS,
            ],
            [
                new EmbedTokenParser('role', 'is', ['...' => ','], 'if(', new CallbackProcessor(fn($role) => true), ") {\n"),
                '{% role is not "admin" %}',
                <<<EOS
                    // line 1
                    if(!( Rebet\View\Engine\Twig\Node\EmbedNode::execute("role", ["admin"]) )) {

                    EOS,
            ],
            [
                new EmbedTokenParser('role', 'in', ['...' => ','], 'if(', new CallbackProcessor(fn($role) => true), ") {\n"),
                '{% role in "admin", "user" %}',
                <<<EOS
                    // line 1
                    if( Rebet\View\Engine\Twig\Node\EmbedNode::execute("role", ["admin", "user"]) ) {

                    EOS,
            ],
            [
                new EmbedTokenParser('role', 'in', ['...' => ','], 'if(', new CallbackProcessor(fn($role) => true), ") {\n"),
                '{% role not in "admin", "user" %}',
                <<<EOS
                    // line 1
                    if(!( Rebet\View\Engine\Twig\Node\EmbedNode::execute("role", ["admin", "user"]) )) {

                    EOS,
            ],
            [
                new EmbedTokenParser('role', 'is', ['...' => [',', 'or']], 'if(', new CallbackProcessor(fn($role) => true), ") {\n"),
                '{% role is "a", "b", "c", "d" or "e" %}',
                <<<EOS
                    // line 1
                    if( Rebet\View\Engine\Twig\Node\EmbedNode::execute("role", ["a", "b", "c", "d", "e"]) ) {

                    EOS,
            ],
            [
                new EmbedTokenParser('role', 'is', ['...' => [',', 'or']], 'if(', new CallbackProcessor(fn($role) => true), ") {\n"),
                '{% role is "a", "b", "c", ("d" or "e") %}',
                <<<EOS
                    // line 1
                    if( Rebet\View\Engine\Twig\Node\EmbedNode::execute("role", ["a", "b", "c", ("d" || "e")]) ) {

                    EOS,
            ],
            [
                new EmbedTokenParser('role', 'is', ['or', ':', '...' => ','], 'if(', new CallbackProcessor(fn($role) => true), ") {\n"),
                '{% role is "a" or "b" : "c", "d", "e" %}',
                <<<EOS
                    // line 1
                    if( Rebet\View\Engine\Twig\Node\EmbedNode::execute("role", ["a", "b", "c", "d", "e"]) ) {

                    EOS,
            ],
            [
                new EmbedTokenParser('role', 'is', ['with', '...' => [',', 'and']], 'if(', new CallbackProcessor(fn($role) => true), ") {\n"),
                '{% role is "a" with "b", "c", "d" and "e" %}',
                <<<EOS
                    // line 1
                    if( Rebet\View\Engine\Twig\Node\EmbedNode::execute("role", ["a", "b", "c", "d", "e"]) ) {

                    EOS,
            ],
            [
                new EmbedTokenParser('role', 'is', ['with', '...' => [',', 'and']], 'if(', new CallbackProcessor(fn($role) => true), ") {\n"),
                '{% role is "a" with "b", "c", "d", "e" %}',
                <<<EOS
                    // line 1
                    if( Rebet\View\Engine\Twig\Node\EmbedNode::execute("role", ["a", "b", "c", "d", "e"]) ) {

                    EOS,
            ],
            [
                new EmbedTokenParser('can', '', [], 'if(', new CallbackProcessor(fn($action) => true), ") {\n"),
                '{% can "update" %}',
                <<<EOS
                    // line 1
                    if( Rebet\View\Engine\Twig\Node\EmbedNode::execute("can", ["update"]) ) {

                    EOS,
            ],
            [
                new EmbedTokenParser('can', '', [], 'if(', new CallbackProcessor(fn($action) => true), ") {\n"),
                '{% can not "update" %}',
                <<<EOS
                    // line 1
                    if(!( Rebet\View\Engine\Twig\Node\EmbedNode::execute("can", ["update"]) )) {

                    EOS,
            ],
            [
                new EmbedTokenParser('can', null, [], 'if(', new CallbackProcessor(fn($action) => true), ") {\n"),
                '{% can not "update" %}',
                <<<EOS
                    // line 1
                    if(!( Rebet\View\Engine\Twig\Node\EmbedNode::execute("can", ["update"]) )) {

                    EOS,
            ],
            [
                new EmbedTokenParser('hello', null, ['??'], 'echo', new CallbackProcessor(fn(...$args) => "Hello dummy"), ';'),
                '{% hello "world" ?? "default" %}',
                <<<EOS
                    // line 1
                    echo Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", ["world", "default"]) ;
                    EOS,
            ],
            [
                new EmbedTokenParser('hello', null, ['??'], 'echo', new CallbackProcessor(fn(...$args) => "Hello dummy"), ';', [], true),
                '{% hello ?? "default" %}',
                <<<EOS
                    // line 1
                    echo Rebet\View\Engine\Twig\Node\EmbedNode::execute("hello", ["default"]) ;
                    EOS,
            ],
        ];
    }

    #[DataProvider('dataParses')]
    public function test_parse(TokenParserInterface $parser, string $source, string $expect): void
    {
        $this->assertSame($expect, $this->renderPhpCode($parser, $source));
    }

    public function test_parse_faile_empty(): void
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage("Too many code arguments. The code tag 'hello' takes no arguments at line 1.");

        $this->renderPhpCode(
            new EmbedTokenParser('hello', null, null, 'echo', new CallbackProcessor(fn(...$args) => "Hello dummy"), ';'),
            '{% hello "a" %}',
        );
    }

    public function test_parse_faile_one(): void
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage("Too many code arguments. The code tag 'hello' takes only one argument at line 1.");

        $this->renderPhpCode(
            new EmbedTokenParser('hello', null, [], 'echo', new CallbackProcessor(fn(...$args) => "Hello dummy"), ';'),
            '{% hello "a" "b" %}',
        );
    }

    public function test_parse_faile_1st(): void
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage("1st and 2nd arguments of the code tag 'hello' must be separated by 'with' at line 1.");

        $this->renderPhpCode(
            new EmbedTokenParser('hello', null, ['with'], 'echo', new CallbackProcessor(fn(...$args) => "Hello dummy"), ';'),
            '{% hello "a", "b" %}',
        );
    }

    public function test_parse_faile_1st2(): void
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage("1st and 2nd arguments of the code tag 'hello' must be separated by ',' or 'or' at line 1.");

        $this->renderPhpCode(
            new EmbedTokenParser('hello', null, [[',', 'or']], 'echo', new CallbackProcessor(fn(...$args) => "Hello dummy"), ';'),
            '{% hello "a" "b" %}',
        );
    }

    public function test_parse_faile_2nd(): void
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage("Too many code arguments. The code tag 'hello' takes up to 2 arguments at line 1.");

        $this->renderPhpCode(
            new EmbedTokenParser('hello', null, [','], 'echo', new CallbackProcessor(fn(...$args) => "Hello dummy"), ';'),
            '{% hello "a", "b", "c" %}',
        );
    }

    protected function renderPhpCode(TokenParserInterface $parser, string $source): string
    {
        $env = new Environment($this->getMockBuilder(LoaderInterface::class)->getMock());
        $env->addTokenParser($parser);
        $stream   = $env->tokenize(new Source($source, ''));
        $parser   = new Parser($env);
        $compiler = new Compiler($env);
        return $compiler->compile($parser->parse($stream)->getNode('body')->getNode('0'))->getSource();
    }
}
