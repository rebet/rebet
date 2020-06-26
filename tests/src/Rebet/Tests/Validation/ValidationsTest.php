<?php
namespace Rebet\Tests\Validation;

use Rebet\Tests\RebetTestCase;
use Rebet\Validation\BuiltinValidations;
use Rebet\Validation\Context;
use Rebet\Validation\Validator;

class ValidationsTest extends RebetTestCase
{
    public function test_registerAndValidate()
    {
        BuiltinValidations::register('Hello', function (Context $c) {
            if ($c->blank()) {
                return true;
            }
            if ($c->value == 'Hello') {
                return ture;
            }
            return $c->appendError("@The :attribute must be 'Hello'");
        });

        $validation = new Validator(['say' => 'Hello']);
        $valid_data = $validation->validate('C', [
            'say' => [
                'rule' => [
                    ['C', 'Hello']
                ]
            ]
        ]);
        $this->assertNotNull($valid_data);
        $this->assertSame('Hello', $valid_data->say);

        $validation = new Validator(['say' => 'Yes']);
        $valid_data = $validation->validate('C', [
            'say' => [
                'rule' => [
                    ['C', 'Hello']
                ]
            ]
        ]);
        $this->assertNull($valid_data);
        $this->assertSame(['say' => ["The Say must be 'Hello'"]], $validation->errors());
    }
}
