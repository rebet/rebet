<?php
namespace Rebet\Tests\Mail\Validator;

use Rebet\Mail\Mail;
use Rebet\Mail\Validator\EmailValidator;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Config\Config;

class EmailValidatorTest extends RebetTestCase
{
    public function test_isValid()
    {
        $validator = new EmailValidator();
        $this->assertSame(false, $validator->isValid('.invalid..rfc.@foo.com', Mail::container()->lookup('email.validation.rfc')));
        $this->assertSame(false, $validator->isValid('.invalid..rfc.@foo.com', Mail::container()->lookup('email.validation.rfc.loose')));
        Mail::clear();
        Config::application([
            Mail::class => [
                'initialize' => [
                    'default' => [
                        'email_validation' => ['email.validation.rfc.loose'],
                    ]
                ]
            ]
        ]);
        $this->assertSame(true, $validator->isValid('.invalid..rfc.@foo.com', Mail::container()->lookup('email.validation.rfc')));
        $this->assertSame(true, $validator->isValid('.invalid..rfc.@foo.com', Mail::container()->lookup('email.validation.rfc.loose')));
    }
}
