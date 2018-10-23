<?php
namespace Rebet\Tests\Validation;

use Rebet\Tests\RebetTestCase;
use Rebet\Validation\Validator;
use Rebet\Foundation\App;
use Rebet\Validation\Valid;
use Rebet\Validation\Context;

class ValidatorTest extends RebetTestCase
{
    public function setup()
    {
        parent::setUp();
        Validator::addValidation('Ok', function (Context $c) {
            return true;
        });
        Validator::addValidation('Ng', function (Context $c) {
            $c->appendError("@The '{$c->label}' is NG.");
            return false;
        });
    }

    public function test_cunstract()
    {
        $validator = new Validator([]);
        $this->assertInstanceOf(Validator::class, $validator);
    }

    /**
     * @dataProvider dataValidateMethods
     */
    public function test_validateMethods(array $data, array $rules, array $errors) : void
    {
        App::setLocale('en');
        $validator    = new Validator($data);
        $valid_data   = $validator->validate('C', $rules);
        $valid_errors = $validator->errors();
        $this->assertSame(empty($valid_errors), !is_null($valid_data));
        $this->assertSame($errors, $valid_errors);
    }

    public function dataValidateMethods() : array
    {
        return [
            // --------------------------------------------
            // Valid::REQUIRED
            // --------------------------------------------
            // @todo When UploadFile
            [
                [],
                ['field_name' => ['rule' => [
                    ['C', Valid::REQUIRED]
                ]]],
                ['field_name' => ["The 'Field Name' field is required."]]
            ],
            [
                ['field_name' => null],
                ['field_name' => ['rule' => [
                    ['C', Valid::REQUIRED]
                ]]],
                ['field_name' => ["The 'Field Name' field is required."]]
            ],
            [
                ['field_name' => ''],
                ['field_name' => ['rule' => [
                    ['C', Valid::REQUIRED]
                ]]],
                ['field_name' => ["The 'Field Name' field is required."]]
            ],
            [
                ['field_name' => []],
                ['field_name' => ['rule' => [
                    ['C', Valid::REQUIRED]
                ]]],
                ['field_name' => ["The 'Field Name' field is required."]]
            ],
            [
                ['field_name' => 0],
                ['field_name' => ['rule' => [
                    ['C', Valid::REQUIRED]
                ]]],
                []
            ],
            [
                ['field_name' => '0'],
                ['field_name' => ['rule' => [
                    ['C', Valid::REQUIRED]
                ]]],
                []
            ],
            [
                ['field_name' => false],
                ['field_name' => ['rule' => [
                    ['C', Valid::REQUIRED]
                ]]],
                []
            ],
            [
                ['field_name' => 'value'],
                ['field_name' => ['rule' => [
                    ['C', Valid::REQUIRED]
                ]]],
                []
            ],
            [
                ['field_name' => ['value']],
                ['field_name' => ['rule' => [
                    ['C', Valid::REQUIRED]
                ]]],
                []
            ],

            // --------------------------------------------
            // Valid::REQUIRED_IF
            // --------------------------------------------
            // @todo When UploadFile
            [
                [],
                ['field_name' => ['rule' => [
                    ['C', Valid::REQUIRED_IF, 'other', 'foo']
                ]]],
                []
            ],
            [
                ['other' => 'bar'],
                ['field_name' => ['rule' => [
                    ['C', Valid::REQUIRED_IF, 'other', 'foo']
                ]]],
                []
            ],
            [
                ['other' => 'foo'],
                ['field_name' => ['rule' => [
                    ['C', Valid::REQUIRED_IF, 'other', 'foo']
                ]]],
                ['field_name' => ["The 'Field Name' field is required when 'Other' is foo."]]
            ],
            [
                ['field_name' => 123, 'other' => 'foo'],
                ['field_name' => ['rule' => [
                    ['C', Valid::REQUIRED_IF, 'other', 'foo']
                ]]],
                []
            ],

            // --------------------------------------------
            // Valid::SATISFY
            // --------------------------------------------
            [
                ['field_name' => 'not_value'],
                ['field_name' => ['rule' => [
                    ['C', Valid::SATISFY, function (Context $c) {
                        if ($c->value !== 'value') {
                            $c->appendError("@The '{$c->label}' is not 'value'.");
                            return false;
                        }
                        return true;
                    }]
                ]]],
                ['field_name' => ["The 'Field Name' is not 'value'."]]
            ],
            [
                ['field_name' => 'value'],
                ['field_name' => ['rule' => [
                    ['C', Valid::SATISFY, function (Context $c) {
                        return $c->value === 'value';
                    },
                    'then' => [['C', 'Ok']],
                    'else' => [['C', 'Ng']]
                    ]
                ]]],
                []
            ],
            [
                ['field_name' => 'not-value'],
                ['field_name' => ['rule' => [
                    ['C', Valid::SATISFY, function (Context $c) {
                        return $c->value === 'value';
                    },
                    'then' => [['C', 'Ok']],
                    'else' => [['C', 'Ng']]
                    ]
                ]]],
                ['field_name' => ["The 'Field Name' is NG."]]
            ],
        ];
    }
}
