<?php
namespace Rebet\Tests\Mail\Validator\Parser;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Exception\DotAtEnd;
use Egulias\EmailValidator\Exception\DotAtStart;
use Rebet\Mail\Validator\Parser\LooseEmailParser;
use Rebet\Tests\RebetTestCase;

class LooseEmailParserTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(LooseEmailParser::class, new LooseEmailParser(new EmailLexer()));
    }

    public function test_ignores()
    {
        $this->assertSame([], (new LooseEmailParser(new EmailLexer()))->ignores());
        $this->assertSame([DotAtEnd::class, DotAtStart::class], (new LooseEmailParser(new EmailLexer(), [DotAtEnd::class, DotAtStart::class]))->ignores());
    }
}
