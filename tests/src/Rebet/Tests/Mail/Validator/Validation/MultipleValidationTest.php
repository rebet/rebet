<?php
namespace Rebet\Tests\Mail\Validator\Validation;

use Rebet\Mail\Mail;
use Rebet\Mail\Validator\Validation\MultipleValidation;
use Rebet\Tests\RebetTestCase;

class MultipleValidationTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(MultipleValidation::class, new MultipleValidation(
            Mail::container()->lookup('email.validation.rfc.loose'),
            Mail::container()->lookup('email.validation.spoof'),
            Mail::container()->lookup('email.validation.dns')
        ));
    }
}
