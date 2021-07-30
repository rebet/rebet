<?php
namespace Rebet\Tests\Mail\Validator\Validation;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Result\Reason\ConsecutiveDot;
use Egulias\EmailValidator\Result\Reason\DotAtEnd;
use Egulias\EmailValidator\Result\Reason\DotAtStart;
use Rebet\Mail\Validator\Validation\LooseRFCValidation;
use Rebet\Tests\RebetTestCase;

class LooseRFCValidationTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(LooseRFCValidation::class, new LooseRFCValidation());
    }

    public function dataIsValids() : array
    {
        return [
            [ true , '.invalid..rfc.mail.@foo.com'      ],
            [ false, '.invalid.rfc.mail@foo.com'    , []],
            [ true , '.invalid.rfc.mail@foo.com'    , [DotAtStart::class]],
            [ false, '.invalid..rfc.mail@foo.com'   , [DotAtStart::class]],
            [ true , '.invalid..rfc.mail@foo.com'   , [DotAtStart::class, ConsecutiveDot::class]],
            [ false, '.invalid..rfc.mail.@foo.com'  , [DotAtStart::class, ConsecutiveDot::class]],
            [ true , '.invalid..rfc.mail.@foo.com'  , [DotAtStart::class, ConsecutiveDot::class, DotAtEnd::class]],
            [ true , '".invalid..rfc.mail."@foo.com', []],
        ];
    }

    /**
     * @dataProvider dataIsValids
     */
    public function test_isValid($expect, string $mail_address, ?array $ignores = null)
    {
        $validation = new LooseRFCValidation($ignores);
        $lexer      = new EmailLexer();
        $lexer->setInput($mail_address);
        $this->assertSame($expect, $validation->isValid($mail_address, $lexer));
    }
}
