<?php
namespace Rebet\Tests\Validation;

use Rebet\Tests\RebetTestCase;
use Rebet\Tests\Validation\Mock\UserValidation;
use Rebet\Validation\BuiltinValidations;
use Rebet\Validation\Context;
use Rebet\Validation\Rule;

class RuleTest extends RebetTestCase
{
    private $errors;
    private $rule;

    public function setup()
    {
        parent::setUp();
        $this->rule       = new UserValidation();
        $this->errors     = [];
    }

    public function test_cunstract()
    {
        $this->assertInstanceOf(Rule::class, $this->rule);
    }

    public function test_hasCustomValidation()
    {
        $this->assertFalse($this->rule->hasCustomValidation('Dummy'));
        $this->assertTrue($this->rule->hasCustomValidation('MailAddressExists'));
    }

    public function test_validate()
    {
        $c = new Context(
            'C',
            ['mail_address' => 'john@rebet.local'],
            $this->errors,
            ['dummy' => []],
            (new BuiltinValidations)->translator()
        );
        $c->initBy('mail_address');
        $this->assertTrue($this->rule->validate('MailAddressExists', $c));
    }
}
