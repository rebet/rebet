<?php

declare(strict_types=1);

namespace Rebet\Tests\Mail\Validator\Parser;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Result\Reason\DotAtEnd;
use Egulias\EmailValidator\Result\Reason\DotAtStart;
use Rebet\Mail\Validator\Parser\LooseEmailParser;
use Rebet\Tests\RebetTestCase;

class LooseEmailParserTest extends RebetTestCase
{
    public function test___construct(): void
    {
        $this->assertInstanceOf(LooseEmailParser::class, new LooseEmailParser(new EmailLexer()));
    }

    public function test_ignores(): void
    {
        $this->assertSame([], (new LooseEmailParser(new EmailLexer()))->ignores());
        $this->assertSame([DotAtEnd::class, DotAtStart::class], (new LooseEmailParser(new EmailLexer(), [DotAtEnd::class, DotAtStart::class]))->ignores());
    }
}
