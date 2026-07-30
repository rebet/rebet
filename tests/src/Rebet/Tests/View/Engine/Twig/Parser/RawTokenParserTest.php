<?php
namespace Rebet\Tests\View\Engine\Twig\Parser;

use PHPUnit\Framework\Attributes\DataProvider;
use Rebet\Tests\RebetTestCase;
use Rebet\View\Engine\Twig\Environment\Environment;
use Rebet\View\Engine\Twig\Parser\RawTokenParser;
use Twig\Compiler;
use Twig\Loader\LoaderInterface;
use Twig\Parser;
use Twig\Source;
use Twig\TokenParser\TokenParserInterface;

class RawTokenParserTest extends RebetTestCase
{
    public function test___constract()
    {
        $this->assertInstanceOf(RawTokenParser::class, new RawTokenParser('endenv', '}'));
    }

    public function test_getTag()
    {
        $paser = new RawTokenParser('endenv', '}');
        $this->assertSame('endenv', $paser->getTag());
    }

    #[DataProvider('dataParses')]
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

    public static function dataParses()
    {
        return [
            [
                new RawTokenParser('endenv', '}'),
                '{% endenv %}',
                '}'
            ],
            [
                new RawTokenParser('endenv', 'endif:'),
                '{% endenv %}',
                'endif:'
            ],
        ];
    }
}
