<?php
namespace Rebet\Tests\Mail\Validator\Validation;

use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\Extra\SpoofCheckValidation;
use Rebet\Mail\Validator\Validation\LooseRFCValidation;
use Rebet\Mail\Validator\Validation\MultipleValidation;
use Rebet\Tests\RebetTestCase;

class MultipleValidationTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(MultipleValidation::class, new MultipleValidation(
            new LooseRFCValidation(),
            new SpoofCheckValidation(),
            new DNSCheckValidation()
        ));
    }
}
