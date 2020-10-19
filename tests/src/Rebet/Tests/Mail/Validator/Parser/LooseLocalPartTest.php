<?php
namespace Rebet\Tests\Mail\Validator\Parser;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Exception\ConsecutiveDot;
use Egulias\EmailValidator\Exception\DotAtEnd;
use Egulias\EmailValidator\Exception\DotAtStart;
use Egulias\EmailValidator\Warning\QuotedString;
use Rebet\Mail\Validator\Parser\LooseLocalPart;
use Rebet\Tests\RebetTestCase;

class LooseLocalPartTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(LooseLocalPart::class, new LooseLocalPart(new EmailLexer()));
    }

    public function test_ignores()
    {
        $this->assertSame([], (new LooseLocalPart(new EmailLexer()))->ignores());
        $this->assertSame([DotAtEnd::class, DotAtStart::class], (new LooseLocalPart(new EmailLexer(), [DotAtEnd::class, DotAtStart::class]))->ignores());
    }

    public function dataParses() : array
    {
        return [
            [
                new DotAtStart(), '.invalid.rfc.mail@foo.com',
            ],
            [
                true, '.invalid.rfc.mail@foo.com', [DotAtStart::class]
            ],
            [
                new ConsecutiveDot(), '.invalid..rfc.mail@foo.com', [DotAtStart::class]
            ],
            [
                true, '.invalid..rfc.mail@foo.com', [DotAtStart::class, ConsecutiveDot::class]
            ],
            [
                new DotAtEnd(), '.invalid..rfc.mail.@foo.com', [DotAtStart::class, ConsecutiveDot::class]
            ],
            [
                true, '.invalid..rfc.mail.@foo.com', [DotAtStart::class, ConsecutiveDot::class, DotAtEnd::class]
            ],
            [
                true, '".invalid..rfc.mail."@foo.com', [], [QuotedString::class]
            ],
        ];
    }

    /**
     * @dataProvider dataParses
     */
    public function test_parse($expect, ?string $mail_address, array $ignores = [], ?array $warnings = null)
    {
        if ($expect instanceof \Exception) {
            $this->expectException(get_class($expect));
        }
        $lexer = new EmailLexer();
        $lexer->setInput($mail_address);
        $lexer->moveNext();
        $lexer->moveNext();
        $parser = new LooseLocalPart($lexer, $ignores);
        $parser->parse($mail_address);
        $this->assertTrue(true);
        $this->assertSame($warnings ?? $ignores, array_values(array_map(function ($v) { return get_class($v); }, $parser->getWarnings())));
    }
}
