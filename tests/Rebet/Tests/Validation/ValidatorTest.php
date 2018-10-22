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
            $c->appendError("@The {$c->label} is NG.");
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
        $validator  = new Validator($data);
        $valid_data = $validator->validate('C', $rules);
        $errors     = $validator->errors();
        $this->assertSame(empty($errors), !is_null($valid_data));
        $this->assertSame($errors, $errors);
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
                ['attribute' => ['rule' => [  ['C', Valid::REQUIRED]  ]]],
                ['attribute' => ["The 'Attribute' field is required."]]
            ],
            [
                ['attribute' => null],
                ['attribute' => ['rule' => [  ['C', Valid::REQUIRED]  ]]],
                ['attribute' => ["The 'Attribute' field is required."]]
            ],
            [
                ['attribute' => ''],
                ['attribute' => ['rule' => [  ['C', Valid::REQUIRED]  ]]],
                ['attribute' => ["The 'Attribute' field is required."]]
            ],
            [
                ['attribute' => []],
                ['attribute' => ['rule' => [  ['C', Valid::REQUIRED]  ]]],
                ['attribute' => ["The 'Attribute' field is required."]]
            ],
            [
                ['attribute' => 0],
                ['attribute' => ['rule' => [  ['C', Valid::REQUIRED]  ]]],
                []
            ],
            [
                ['attribute' => '0'],
                ['attribute' => ['rule' => [  ['C', Valid::REQUIRED]  ]]],
                []
            ],
            [
                ['attribute' => false],
                ['attribute' => ['rule' => [  ['C', Valid::REQUIRED]  ]]],
                []
            ],
            [
                ['attribute' => 'value'],
                ['attribute' => ['rule' => [  ['C', Valid::REQUIRED]  ]]],
                []
            ],
            [
                ['attribute' => ['value']],
                ['attribute' => ['rule' => [  ['C', Valid::REQUIRED]  ]]],
                []
            ],


            
            // --------------------------------------------
            // Valid::IF
            // --------------------------------------------
            [
                ['attribute' => ['value']],
                ['attribute' => ['rule' => [  ['C', Valid::IF, function (Context $c) {
                    return $c->value === 'value';
                }, 'then' => ['C', 'Ok'], 'else' => ['C', 'Ng'] ]  ]]],
                []
            ],
            [
                ['attribute' => ['not-value']],
                ['attribute' => ['rule' => [  ['C', Valid::IF, function (Context $c) {
                    return $c->value === 'value';
                }, 'then' => ['C', 'Ok'], 'else' => ['C', 'Ng'] ]  ]]],
                ['attribute' => ["The 'Attribute' is NG."]]
            ],
        ];
    }
}
