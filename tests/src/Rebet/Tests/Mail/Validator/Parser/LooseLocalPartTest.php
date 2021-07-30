<?php
namespace Rebet\Tests\Mail\Validator\Parser;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Result\Reason\ConsecutiveDot;
use Egulias\EmailValidator\Result\Reason\DotAtEnd;
use Egulias\EmailValidator\Result\Reason\DotAtStart;
use Egulias\EmailValidator\Warning\QuotedString;
use Rebet\Mail\Validator\Parser\LooseLocalPart;
use Rebet\Mail\Validator\Warning\ConsecutiveDotWarning;
use Rebet\Mail\Validator\Warning\DotAtEndWarning;
use Rebet\Mail\Validator\Warning\DotAtStartWarning;
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
                new DotAtStart(), '.invalid.rfc.mail@foo.com', []
            ],
            [
                null, '.invalid.rfc.mail@foo.com', [DotAtStart::class], [DotAtStartWarning::class]
            ],
            [
                new ConsecutiveDot(), '.invalid..rfc.mail@foo.com', [DotAtStart::class], [DotAtStartWarning::class]
            ],
            [
                null, '.invalid..rfc.mail@foo.com', [DotAtStart::class, ConsecutiveDot::class], [DotAtStartWarning::class, ConsecutiveDotWarning::class]
            ],
            [
                new DotAtEnd(), '.invalid..rfc.mail.@foo.com', [DotAtStart::class, ConsecutiveDot::class], [DotAtStartWarning::class, ConsecutiveDotWarning::class]
            ],
            [
                null, '.invalid..rfc.mail.@foo.com', [DotAtStart::class, ConsecutiveDot::class, DotAtEnd::class], [DotAtStartWarning::class, ConsecutiveDotWarning::class, DotAtEndWarning::class]
            ],
            [
                null, '".invalid..rfc.mail."@foo.com', [], [QuotedString::class]
            ],
        ];
    }

    /**
     * @dataProvider dataParses
     */
    public function test_parse($expect, ?string $mail_address, array $ignores, ?array $warnings = null)
    {
        $lexer = new EmailLexer();
        $lexer->setInput($mail_address);
        $lexer->moveNext();
        $lexer->moveNext();
        $parser = new LooseLocalPart($lexer, $ignores);
        $result = $parser->parse($mail_address);
        if($expect !== null) {
            $this->assertTrue($result->isInvalid());
            $this->assertEquals($expect, $result->reason());
        } else {
            $this->assertTrue($result->isValid());
        }
        $this->assertSame($warnings ?? $ignores, array_values(array_map(function ($v) { return get_class($v); }, $parser->getWarnings())));
    }
}
