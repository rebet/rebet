<?php
namespace Rebet\Tests\Validation;

use Rebet\DateTime\DateTime;
use Rebet\Foundation\App;
use Rebet\Tests\Mock\Gender;
use Rebet\Tests\RebetTestCase;
use Rebet\Validation\BuiltinValidations;
use Rebet\Validation\Context;
use Rebet\Validation\Valid;

class BuiltinValidationsTest extends RebetTestCase
{
    private $validations;

    public function setup()
    {
        parent::setUp();
        App::setLocale('en');
        App::setTimezone('UTC');
        DateTime::setTestNow('2010-01-23 12:34:56');
        $this->validations = new BuiltinValidations();
    }

    public function test_cunstract()
    {
        $validations = new BuiltinValidations();
        $this->assertInstanceOf(BuiltinValidations::class, $validations);
    }
    
    /**
     * @dataProvider dataValidationMethods
     */
    public function test_validationMethods(array $record) : void
    {
        extract($record);
        $errors = [];
        foreach ($tests as $i => [$field, $args, $expect_valid, $expect_errors]) {
            $c = new Context('C', $data, $errors, []);
            $c->initBy($field);
            $errors  = [];
            $valid   = $this->validations->validate($name, $c, ...$args);
            $message = "Failed [NAME: {$name} IDX: {$i} FIELD: {$field}]";
            $this->assertSame($expect_valid, $valid, "{$message} result '{$valid}' failed.");
            $this->assertSame($expect_errors, $errors, "{$message} error messages unmatched.");
        }
    }

    public function dataValidationMethods() : array
    {
        $this->setUp();
        $ng_word_file = App::path('/resources/validation/ng_word.txt');

        return [
            // --------------------------------------------
            // Valid::IF
            // --------------------------------------------
            [[
                'name'  => 'If',
                'data'  => ['foo' => 1, 'bar' => 1, 'baz' => 2],
                'tests' => [
                    ['foo', ['bar', 1        ], true , []],
                    ['foo', ['bar', 2        ], false, []],
                    ['foo', ['bar', ':foo'   ], true , []],
                    ['foo', ['bar', ':baz'   ], false, []],
                    ['foo', ['bar', [1, 3, 5]], true , []],
                    ['foo', ['bar', [2, 4, 6]], false, []],
                ]
            ]],
            
            // --------------------------------------------
            // Valid::UNLESS
            // --------------------------------------------
            [[
                'name'  => 'Unless',
                'data'  => ['foo' => 1, 'bar' => 1, 'baz' => 2],
                'tests' => [
                    ['foo', ['bar', 1        ], false, []],
                    ['foo', ['bar', 2        ], true , []],
                    ['foo', ['bar', ':foo'   ], false, []],
                    ['foo', ['bar', ':baz'   ], true , []],
                    ['foo', ['bar', [1, 3, 5]], false, []],
                    ['foo', ['bar', [2, 4, 6]], true , []],
                ]
            ]],

            // --------------------------------------------
            // Valid::WITH
            // --------------------------------------------
            [[
                'name'  => 'With',
                'data'  => ['foo' => null, 'bar' => 1, 'baz' => 2],
                'tests' => [
                    ['foo', ['foo'            ], false, []],
                    ['foo', ['bar'            ], true , []],
                    ['foo', [['foo', 'bar']   ], false, []],
                    ['foo', [['bar', 'baz']   ], true , []],
                    ['foo', [['foo', 'bar'], 1], true, []],
                ]
            ]],
            
            // --------------------------------------------
            // Valid::WITHOUT
            // --------------------------------------------
            [[
                'name'  => 'Without',
                'data'  => ['foo' => 1, 'bar' => null, 'baz' => null],
                'tests' => [
                    ['foo', ['foo'            ], false, []],
                    ['foo', ['bar'            ], true , []],
                    ['foo', [['foo', 'bar']   ], false, []],
                    ['foo', [['bar', 'baz']   ], true , []],
                    ['foo', [['foo', 'bar'], 1], true, []],
                ]
            ]],
            
            // --------------------------------------------
            // Valid::IF_NO_ERROR
            // --------------------------------------------
            [[
                'name'  => 'IfNoError',
                'data'  => ['foo' => 1, 'bar' => 1, 'baz' => 2],
                'tests' => [
                    ['foo', [     ], true , []],
                    ['foo', ['bar'], true , []],
                ]
            ]],
            
            // --------------------------------------------
            // Valid::IF_AN_ERROR
            // --------------------------------------------
            [[
                'name'  => 'IfAnError',
                'data'  => ['foo' => 1, 'bar' => 1, 'baz' => 2],
                'tests' => [
                    ['foo', [     ], false, []],
                    ['foo', ['bar'], false, []],
                ]
            ]],

            // --------------------------------------------
            // Valid::SATISFY
            // --------------------------------------------
            [[
                'name'  => 'Satisfy',
                'data'  => ['foo' => 1, 'bar' => 2, 'baz' => 2],
                'tests' => [
                    ['foo', [function (Context $c) { return $c->value == 1 ? true : $c->appendError("@The {$c->label} is not 1.") ; }], true , []],
                    ['bar', [function (Context $c) { return $c->value == 1 ? true : $c->appendError("@The {$c->label} is not 1.") ; }], false, ['bar' => ["The Bar is not 1."]]],
                    ['foo', [function (Context $c) { return $c->value == 1; }                                                        ], true , []],
                    ['bar', [function (Context $c) { return $c->value == 1; }                                                        ], false, []],
                ]
            ]],

            // --------------------------------------------
            // Valid::REQUIRED
            // --------------------------------------------
            // @todo When UploadFile
            [[
                'name'  => 'Required',
                'data'  => ['null' => null, 'empty_string' => '', 'empty_array' => [], 'zero' => 0, 'zero_string' => '0', 'false' => false, 'array' => [1]],
                'tests' => [
                    ['nothing'     , [], false, ['nothing'      => ["The Nothing field is required."]]],
                    ['null'        , [], false, ['null'         => ["The Null field is required."]]],
                    ['empty_string', [], false, ['empty_string' => ["The Empty String field is required."]]],
                    ['empty_array' , [], false, ['empty_array'  => ["The Empty Array field is required."]]],
                    ['zero'        , [], true , []],
                    ['zero_string' , [], true , []],
                    ['false'       , [], true , []],
                    ['array'       , [], true , []],
                ]
            ]],

            // --------------------------------------------
            // Valid::REQUIRED_IF
            // --------------------------------------------
            [[
                'name'  => 'RequiredIf',
                'data'  => ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => 2],
                'tests' => [
                    ['foo', ['nothing', 1        ], true , []],
                    ['foo', ['baz'    , 1        ], true , []],
                    ['foo', ['baz'    , 2        ], false, ['foo' => ["The Foo field is required when Baz is 2."]]],
                    ['bar', ['baz'    , 2        ], true , []],
                    ['foo', ['baz'    , [2, 4, 6]], false, ['foo' => ["The Foo field is required when Baz is in 2, 4, 6."]]],
                    ['foo', ['baz'    , [1, 3, 5]], true , []],
                    ['foo', ['baz'    , ':bar'   ], true , []],
                    ['foo', ['baz'    , ':qux'   ], false, ['foo' => ["The Foo field is required when Baz is Qux."]]],
                ]
            ]],

            // --------------------------------------------
            // Valid::REQUIRED_UNLESS
            // --------------------------------------------
            [[
                'name'  => 'RequiredUnless',
                'data'  => ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => 2],
                'tests' => [
                    ['foo', ['nothing', 1        ], false, ['foo' => ["The Foo field is required when Nothing is not 1."]]],
                    ['foo', ['baz'    , 1        ], false, ['foo' => ["The Foo field is required when Baz is not 1."]]],
                    ['bar', ['baz'    , 1        ], true , []],
                    ['foo', ['baz'    , 2        ], true , []],
                    ['foo', ['baz'    , [2, 4, 6]], true , []],
                    ['foo', ['baz'    , [1, 3, 5]], false, ['foo' => ["The Foo field is required when Baz is not in 1, 3, 5."]]],
                    ['foo', ['baz'    , ':bar'   ], false, ['foo' => ["The Foo field is required when Baz is not Bar."]]],
                    ['foo', ['baz'    , ':qux'   ], true , []],
                ]
            ]],

            // --------------------------------------------
            // Valid::REQUIRED_WITH
            // --------------------------------------------
            [[
                'name'  => 'RequiredWith',
                'data'  => ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                'tests' => [
                    ['foo', ['nothing'               ], true , []],
                    ['foo', ['bar'                   ], false, ['foo' => ["The Foo field is required when Bar is present."]]],
                    ['baz', ['bar'                   ], true , []],
                    ['foo', [['bar', 'baz']          ], false, ['foo' => ["The Foo field is required when Bar, Baz are present."]]],
                    ['foo', [['bar', 'baz', 'qux']   ], true , []],
                    ['foo', [['bar', 'baz', 'qux'], 2], false, ['foo' => ["The Foo field is required when Bar, Baz, Qux are present at least 2."]]],
                    ['foo', [['qux', 'quxx']      , 1], true , []],
                ]
            ]],

            // --------------------------------------------
            // Valid::REQUIRED_WITHOUT
            // --------------------------------------------
            [[
                'name'  => 'RequiredWithout',
                'data'  => ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                'tests' => [
                    ['foo', ['nothing'                ], false, ['foo' => ["The Foo field is required when Nothing is not present."]]],
                    ['foo', ['qux'                    ], false, ['foo' => ["The Foo field is required when Qux is not present."]]],
                    ['baz', ['qux'                    ], true , []],
                    ['foo', [['qux', 'quux']          ], false, ['foo' => ["The Foo field is required when Qux, Quux are not present."]]],
                    ['foo', [['qux', 'quux', 'bar']   ], true , []],
                    ['foo', [['qux', 'quux', 'bar'], 2], false, ['foo' => ["The Foo field is required when Qux, Quux, Bar are not present at least 2."]]],
                    ['foo', [['qux', 'bar', 'baz'] , 2], true , []],
                ]
            ]],

            // --------------------------------------------
            // Valid::BLANK_IF
            // --------------------------------------------
            [[
                'name'  => 'BlankIf',
                'data'  => ['foo' => 1, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                'tests' => [
                    ['foo', ['nothing', 2        ], true , []],
                    ['foo', ['bar'    , 2        ], true , []],
                    ['foo', ['baz'    , 2        ], false, ['foo' => ["The Foo field must be blank when Baz is 2."]]],
                    ['qux', ['baz'    , 2        ], true , []],
                    ['foo', ['baz'    , [2, 4, 6]], false, ['foo' => ["The Foo field must be blank when Baz is in 2, 4, 6."]]],
                    ['foo', ['baz'    , [1, 3, 5]], true , []],
                    ['foo', ['bar'    , ':baz'   ], true , []],
                    ['foo', ['foo'    , ':bar'   ], false, ['foo' => ["The Foo field must be blank when Foo is Bar."]]],
                    ['foo', ['qux'    , ':quux'  ], false, ['foo' => ["The Foo field must be blank when Qux is Quux."]]],
                ]
            ]],

            // --------------------------------------------
            // Valid::BLANK_UNLESS
            // --------------------------------------------
            [[
                'name'  => 'BlankUnless',
                'data'  => ['foo' => 1, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                'tests' => [
                    ['foo', ['nothing', 2        ], false, ['foo' => ["The Foo field must be blank when Nothing is not 2."]]],
                    ['foo', ['bar'    , 2        ], false, ['foo' => ["The Foo field must be blank when Bar is not 2."]]],
                    ['foo', ['baz'    , 2        ], true , []],
                    ['qux', ['bar'    , 2        ], true , []],
                    ['foo', ['baz'    , [2, 4, 6]], true , []],
                    ['foo', ['baz'    , [1, 3, 5]], false, ['foo' => ["The Foo field must be blank when Baz is not in 1, 3, 5."]]],
                    ['foo', ['bar'    , ':baz'   ], false, ['foo' => ["The Foo field must be blank when Bar is not Baz."]]],
                    ['foo', ['foo'    , ':bar'   ], true , []],
                    ['foo', ['qux'    , ':quux'  ], true , []],
                ]
            ]],

            // --------------------------------------------
            // Valid::BLANK_WITH
            // --------------------------------------------
            [[
                'name'  => 'BlankWith',
                'data'  => ['foo' => 1, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                'tests' => [
                    ['foo', ['nothing'               ], true , []],
                    ['foo', ['bar'                   ], false, ['foo' => ["The Foo field must be blank when Bar is present."]]],
                    ['qux', ['bar'                   ], true , []],
                    ['foo', [['bar', 'baz']          ], false, ['foo' => ["The Foo field must be blank when Bar, Baz are present."]]],
                    ['foo', [['bar', 'baz', 'qux']   ], true , []],
                    ['foo', [['bar', 'baz', 'qux'], 2], false, ['foo' => ["The Foo field must be blank when Bar, Baz, Qux are present at least 2."]]],
                    ['foo', [['qux', 'quxx']      , 1], true , []],
                ]
            ]],

            // --------------------------------------------
            // Valid::BLANK_WITHOUT
            // --------------------------------------------
            [[
                'name'  => 'BlankWithout',
                'data'  => ['foo' => 1, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                'tests' => [
                    ['foo', ['nothing'                ], false, ['foo' => ["The Foo field must be blank when Nothing is not present."]]],
                    ['foo', ['qux'                    ], false, ['foo' => ["The Foo field must be blank when Qux is not present."]]],
                    ['qux', ['qux'                    ], true , []],
                    ['foo', [['qux', 'quux']          ], false, ['foo' => ["The Foo field must be blank when Qux, Quux are not present."]]],
                    ['foo', [['qux', 'quux', 'bar']   ], true , []],
                    ['foo', [['qux', 'quux', 'bar'], 2], false, ['foo' => ["The Foo field must be blank when Qux, Quux, Bar are not present at least 2."]]],
                    ['foo', [['qux', 'bar', 'baz'] , 2], true , []],
                ]
            ]],

            // --------------------------------------------
            // Valid::SAME_AS
            // --------------------------------------------
            [[
                'name'  => 'SameAs',
                'data'  => ['foo' => 1, 'bar' => 1, 'baz' => 2, 'qux' => null],
                'tests' => [
                    ['nothing', [1               ], true , []],
                    ['qux'    , [1               ], true , []],
                    ['foo'    , [1               ], true , []],
                    ['foo'    , [2               ], false, ['foo' => ["The Foo and 2 must match."]]],
                    ['foo'    , [Gender::FEMALE()], false, ['foo' => ["The Foo and Female must match."]]],
                    ['foo'    , [':bar'          ], true , []],
                    ['foo'    , [':baz'          ], false, ['foo' => ["The Foo and Baz must match."]]],
                ]
            ]],

            // --------------------------------------------
            // Valid::NOT_SAME_AS
            // --------------------------------------------
            [[
                'name'  => 'NotSameAs',
                'data'  => ['foo' => 1, 'bar' => 1, 'baz' => 2, 'qux' => null],
                'tests' => [
                    ['nothing', [1     ], true , []],
                    ['qux'    , [1     ], true , []],
                    ['foo'    , [1     ], false, ['foo' => ["The Foo and 1 must not match."]]],
                    ['foo'    , [2     ], true , []],
                    ['foo'    , [':bar'], false, ['foo' => ["The Foo and Bar must not match."]]],
                    ['foo'    , [':baz'], true , []],
                ]
            ]],

            // --------------------------------------------
            // Valid::REGEX
            // --------------------------------------------
            [[
                'name'  => 'Regex',
                'data'  => ['null' => null, 'foo' => 1, 'bar' => '123', 'baz' => 'abc', 'qux' => ['123', '456', '789'], 'quux' => ['123', 'abc', 'def']],
                'tests' => [
                    ['nothing', ['/^[0-9]+$/'          ], true , []],
                    ['null'   , ['/^[0-9]+$/'          ], true , []],
                    ['foo'    , ['/^[0-9]+$/'          ], true , []],
                    ['bar'    , ['/^[0-9]+$/'          ], true , []],
                    ['baz'    , ['/^[0-9]+$/'          ], false, ['baz'  => ["The Baz format is invalid."]]],
                    ['baz'    , ['/^[0-9]+$/', 'digits'], false, ['baz'  => ["The Baz must be digits."]]],
                    ['qux'    , ['/^[0-9]+$/'          ], true , []],
                    ['quux'   , ['/^[0-9]+$/'          ], false, ['quux' => [
                        "The 2nd Quux (abc) format is invalid.",
                        "The 3rd Quux (def) format is invalid.",
                    ]]],
                    ['quux'   , ['/^[0-9]+$/', 'digits'], false, ['quux' => [
                        "The 2nd Quux (abc) must be digits.",
                        "The 3rd Quux (def) must be digits.",
                    ]]],
                ]
            ]],

            // --------------------------------------------
            // Valid::NOT_REGEX
            // --------------------------------------------
            [[
                'name'  => 'NotRegex',
                'data'  => ['null' => null, 'foo' => 1, 'bar' => 'abc', 'baz' => '123', 'qux' => ['abc', 'def', 'ghi'], 'quux' => ['abc', '123', '456']],
                'tests' => [
                    ['nothing', ['/^[0-9]+$/'          ], true , []],
                    ['null'   , ['/^[0-9]+$/'          ], true , []],
                    ['foo'    , ['/^[0-9]+$/'          ], false, ['foo'  => ["The Foo format is invalid."]]],
                    ['bar'    , ['/^[0-9]+$/'          ], true , []],
                    ['baz'    , ['/^[0-9]+$/'          ], false, ['baz'  => ["The Baz format is invalid."]]],
                    ['baz'    , ['/^[0-9]+$/', 'digits'], false, ['baz'  => ["The Baz must contain non-digits characters."]]],
                    ['qux'    , ['/^[0-9]+$/'          ], true , []],
                    ['quux'   , ['/^[0-9]+$/'          ], false, ['quux' => [
                        "The 2nd Quux (123) format is invalid.",
                        "The 3rd Quux (456) format is invalid.",
                    ]]],
                    ['quux'   , ['/^[0-9]+$/', 'digits'], false, ['quux' => [
                        "The 2nd Quux (123) must contain non-digits characters.",
                        "The 3rd Quux (456) must contain non-digits characters.",
                    ]]],
                ]
            ]],

            // --------------------------------------------
            // Valid::MAX_LENGTH
            // --------------------------------------------
            [[
                'name'  => 'MaxLength',
                'data'  => ['null' => null, 'foo' => 'abc', 'bar' => 'abcd', 'baz' => ['1234', '1', '123', '12345']],
                'tests' => [
                    ['nothing', [3], true , []],
                    ['null'   , [3], true , []],
                    ['foo'    , [3], true , []],
                    ['bar'    , [3], false, ['bar' => ["The Bar may not be greater than 3 characters."]]],
                    ['baz'    , [3], false, ['baz' => [
                        "The 1st Baz (1234) may not be greater than 3 characters.",
                        "The 4th Baz (12345) may not be greater than 3 characters.",
                    ]]],
                ]
            ]],

            // --------------------------------------------
            // Valid::MIN_LENGTH
            // --------------------------------------------
            [[
                'name'  => 'MinLength',
                'data'  => ['null' => null, 'foo' => 'abc', 'bar' => 'ab', 'baz' => ['1234', '1', '123']],
                'tests' => [
                    ['nothing', [3], true , []],
                    ['null'   , [3], true , []],
                    ['foo'    , [3], true , []],
                    ['bar'    , [3], false, ['bar' => ["The Bar must be at least 3 characters."]]],
                    ['baz'    , [3], false, ['baz' => [
                        "The 2nd Baz (1) must be at least 3 characters.",
                    ]]],
                ]
            ]],

            // --------------------------------------------
            // Valid::LENGTH
            // --------------------------------------------
            [[
                'name'  => 'Length',
                'data'  => ['null' => null, 'foo' => 'abc', 'bar' => 'ab', 'baz' => 'abcd', 'qux' => ['1234', '1', '123']],
                'tests' => [
                    ['nothing', [3], true , []],
                    ['null'   , [3], true , []],
                    ['foo'    , [3], true , []],
                    ['bar'    , [3], false, ['bar' => ["The Bar must be 3 characters."]]],
                    ['baz'    , [3], false, ['baz' => ["The Baz must be 3 characters."]]],
                    ['qux'    , [3], false, ['qux' => [
                        "The 1st Qux (1234) must be 3 characters.",
                        "The 2nd Qux (1) must be 3 characters.",
                    ]]],
                ]
            ]],

            // --------------------------------------------
            // Valid::NUMBER
            // --------------------------------------------
            [[
                'name'  => 'Number',
                'data'  => ['null' => null, 'foo' => '123', 'bar' => 'abc', 'baz' => ['+123.4', '-1234', '1.234', '123'], 'qux' => ['+123.4', '-1,234', '1.234']],
                'tests' => [
                    ['nothing', [], true , []],
                    ['null'   , [], true , []],
                    ['foo'    , [], true , []],
                    ['bar'    , [], false, ['bar' => ["The Bar must be number."]]],
                    ['baz'    , [], true , []],
                    ['qux'    , [], false, ['qux' => [
                        "The 2nd Qux (-1,234) must be number.",
                    ]]],
                ]
            ]],

            // --------------------------------------------
            // Valid::INTEGER
            // --------------------------------------------
            [[
                'name'  => 'Integer',
                'data'  => ['null' => null, 'foo' => '123', 'bar' => 'abc', 'baz' => ['+123', '-1234', '+1234'], 'qux' => ['+123.4', '123', 'abc']],
                'tests' => [
                    ['nothing', [], true , []],
                    ['null'   , [], true , []],
                    ['foo'    , [], true , []],
                    ['bar'    , [], false, ['bar' => ["The Bar must be integer."]]],
                    ['baz'    , [], true , []],
                    ['qux'    , [], false, ['qux' => [
                        "The 1st Qux (+123.4) must be integer.",
                        "The 3rd Qux (abc) must be integer.",
                    ]]],
                ]
            ]],
            
            // --------------------------------------------
            // Valid::FLOAT
            // --------------------------------------------
            [[
                'name'  => 'Float',
                'data'  => ['null' => null, 'foo' => '123', 'bar' => '123.12', 'baz' => '123.123', 'qux' => 'abc', 'quux' => ['+123', '-123.4', '+12.34'], 'foobar' => ['+123.4', '123.230', 'abc']],
                'tests' => [
                    ['nothing', [2], true , []],
                    ['null'   , [2], true , []],
                    ['foo'    , [2], true , []],
                    ['bar'    , [2], true , []],
                    ['baz'    , [2], false, ['baz'    => ["The Baz must be real number (up to 2 decimal places)."]]],
                    ['qux'    , [2], false, ['qux'    => ["The Qux must be real number (up to 2 decimal places)."]]],
                    ['quux'   , [2], true , []],
                    ['foobar' , [2], false, ['foobar' => [
                        "The 2nd Foobar (123.230) must be real number (up to 2 decimal places).",
                        "The 3rd Foobar (abc) must be real number (up to 2 decimal places).",
                    ]]],
                ]
            ]],

            // --------------------------------------------
            // Valid::NUMBER_LESS_THAN
            // --------------------------------------------
            [[
                'name'  => 'NumberLessThan',
                'data'  => ['null' => null, 'foo' => '10', 'bar' => '-11', 'baz' => '11', 'qux' => '10.1', 'quux' => 'abc', 'foobar' => ['abc', '10', '2', 123, '3.5']],
                'tests' => [
                    ['nothing', [10    ], true , []],
                    ['null'   , [10    ], true , []],
                    ['foo'    , [10    ], false, ['foo'    => ["The Foo must be less than 10."]]],
                    ['foo'    , [':bar'], false, ['foo'    => ["The Foo must be less than Bar."]]],
                    ['bar'    , [10    ], true , []],
                    ['baz'    , [10    ], false, ['baz'    => ["The Baz must be less than 10."]]],
                    ['qux'    , [10    ], false, ['qux'    => ["The Qux must be integer."]]],
                    ['qux'    , [10, 1 ], false, ['qux'    => ["The Qux must be less than 10."]]],
                    ['quux'   , [10    ], false, ['quux'   => ["The Quux must be integer."]]],
                    ['quux'   , [10, 1 ], false, ['quux'   => ["The Quux must be real number (up to 1 decimal places)."]]],
                    ['foobar' , [10    ], false, ['foobar' => [
                        "The 1st Foobar (abc) must be integer.",
                        "The 5th Foobar (3.5) must be integer.",
                        "The 2nd Foobar (10) must be less than 10.",
                        "The 4th Foobar (123) must be less than 10.",
                    ]]],
                    ['foobar' , [10, 1 ], false, ['foobar' => [
                        "The 1st Foobar (abc) must be real number (up to 1 decimal places).",
                        "The 2nd Foobar (10) must be less than 10.",
                        "The 4th Foobar (123) must be less than 10.",
                    ]]],
                ]
            ]],

            // --------------------------------------------
            // Valid::NUMBER_LESS_THAN_OR_EQUAL
            // --------------------------------------------
            [[
                'name'  => 'NumberLessThanOrEqual',
                'data'  => ['null' => null, 'foo' => '10', 'bar' => '-11', 'baz' => '11', 'qux' => '10.1', 'quux' => 'abc', 'foobar' => ['abc', '10', '2', 123, '3.5']],
                'tests' => [
                    ['nothing', [10    ], true , []],
                    ['null'   , [10    ], true , []],
                    ['foo'    , [10    ], true , []],
                    ['foo'    , [':bar'], false, ['foo'    => ["The Foo may not be greater than Bar."]]],
                    ['bar'    , [10    ], true , []],
                    ['baz'    , [10    ], false, ['baz'    => ["The Baz may not be greater than 10."]]],
                    ['qux'    , [10    ], false, ['qux'    => ["The Qux must be integer."]]],
                    ['qux'    , [10, 1 ], false, ['qux'    => ["The Qux may not be greater than 10."]]],
                    ['quux'   , [10    ], false, ['quux'   => ["The Quux must be integer."]]],
                    ['quux'   , [10, 1 ], false, ['quux'   => ["The Quux must be real number (up to 1 decimal places)."]]],
                    ['foobar' , [10    ], false, ['foobar' => [
                        "The 1st Foobar (abc) must be integer.",
                        "The 5th Foobar (3.5) must be integer.",
                        "The 4th Foobar (123) may not be greater than 10.",
                    ]]],
                    ['foobar' , [10, 1 ], false, ['foobar' => [
                        "The 1st Foobar (abc) must be real number (up to 1 decimal places).",
                        "The 4th Foobar (123) may not be greater than 10.",
                    ]]],
                ]
            ]],
            
            // --------------------------------------------
            // Valid::NUMBER_GREATER_THAN
            // --------------------------------------------
            [[
                'name'  => 'NumberGreaterThan',
                'data'  => ['null' => null, 'foo' => '10', 'bar' => '-11', 'baz' => '11', 'qux' => '10.1', 'quux' => 'abc', 'foobar' => ['abc', '10', '2', 123, '3.5']],
                'tests' => [
                    ['nothing', [10    ], true , []],
                    ['null'   , [10    ], true , []],
                    ['foo'    , [10    ], false, ['foo'    => ["The Foo must be greater than 10."]]],
                    ['foo'    , [':baz'], false, ['foo'    => ["The Foo must be greater than Baz."]]],
                    ['bar'    , [10    ], false, ['bar'    => ["The Bar must be greater than 10."]]],
                    ['baz'    , [10    ], true , []],
                    ['qux'    , [10    ], false, ['qux'    => ["The Qux must be integer."]]],
                    ['qux'    , [10, 1 ], true , []],
                    ['quux'   , [10    ], false, ['quux'   => ["The Quux must be integer."]]],
                    ['quux'   , [10, 1 ], false, ['quux'   => ["The Quux must be real number (up to 1 decimal places)."]]],
                    ['foobar' , [10    ], false, ['foobar' => [
                        "The 1st Foobar (abc) must be integer.",
                        "The 5th Foobar (3.5) must be integer.",
                        "The 2nd Foobar (10) must be greater than 10.",
                        "The 3rd Foobar (2) must be greater than 10.",
                    ]]],
                    ['foobar' , [10, 1 ], false, ['foobar' => [
                        "The 1st Foobar (abc) must be real number (up to 1 decimal places).",
                        "The 2nd Foobar (10) must be greater than 10.",
                        "The 3rd Foobar (2) must be greater than 10.",
                        "The 5th Foobar (3.5) must be greater than 10.",
                    ]]],
                ]
            ]],

            // --------------------------------------------
            // Valid::NUMBER_GREATER_THAN_OR_EQUAL
            // --------------------------------------------
            [[
                'name'  => 'NumberGreaterThanOrEqual',
                'data'  => ['null' => null, 'foo' => '10', 'bar' => '-11', 'baz' => '11', 'qux' => '10.1', 'quux' => 'abc', 'foobar' => ['abc', '10', '2', 123, '3.5']],
                'tests' => [
                    ['nothing', [10    ], true , []],
                    ['null'   , [10    ], true , []],
                    ['foo'    , [10    ], true , []],
                    ['foo'    , [':baz'], false, ['foo'    => ["The Foo must be at least Baz."]]],
                    ['bar'    , [10    ], false, ['bar'    => ["The Bar must be at least 10."]]],
                    ['baz'    , [10    ], true , []],
                    ['qux'    , [10    ], false, ['qux'    => ["The Qux must be integer."]]],
                    ['qux'    , [10, 1 ], true , []],
                    ['quux'   , [10    ], false, ['quux'   => ["The Quux must be integer."]]],
                    ['quux'   , [10, 1 ], false, ['quux'   => ["The Quux must be real number (up to 1 decimal places)."]]],
                    ['foobar' , [10    ], false, ['foobar' => [
                        "The 1st Foobar (abc) must be integer.",
                        "The 5th Foobar (3.5) must be integer.",
                        "The 3rd Foobar (2) must be at least 10.",
                    ]]],
                    ['foobar' , [10, 1 ], false, ['foobar' => [
                        "The 1st Foobar (abc) must be real number (up to 1 decimal places).",
                        "The 3rd Foobar (2) must be at least 10.",
                        "The 5th Foobar (3.5) must be at least 10.",
                    ]]],
                ]
            ]],

            // --------------------------------------------
            // Valid::EMAIL
            // --------------------------------------------
            [[
                'name'  => 'Email',
                'data'  => ['null' => null, 'foo' => 'foo@rebet.local', 'bar' => '.bar@rebet.local', 'baz' => ['foo@rebet.local', '.bar@rebet.local', 'abc', 'foo.bar@rebet.local', 'foo..baz@rebet.local']],
                'tests' => [
                    ['nothing', [     ], true , []],
                    ['null'   , [     ], true , []],
                    ['foo'    , [     ], true , []],
                    ['bar'    , [     ], false, ['bar' => ["The Bar must be a valid email address."]]],
                    ['bar'    , [false], true , []],
                    ['baz'    , [     ], false, ['baz' => [
                        "The 2nd Baz (.bar@rebet.local) must be a valid email address.",
                        "The 3rd Baz (abc) must be a valid email address.",
                        "The 5th Baz (foo..baz@rebet.local) must be a valid email address.",
                    ]]],
                    ['baz'    , [false], false, ['baz' => [
                        "The 3rd Baz (abc) must be a valid email address.",
                    ]]],
                ]
            ]],

            // --------------------------------------------
            // Valid::URL
            // --------------------------------------------
            [[
                'name'  => 'Url',
                'data'  => ['null' => null, 'foo' => 'https://github.com/rebet/rebet', 'bar' => 'https://invalid[bar]/rebet', 'baz' => 'https://invalid.local/rebet', 'qux' => ['https://github.com/rebet/rebet', 'https://invalid[bar]/rebet', 'https://invalid.local/rebet']],
                'tests' => [
                    ['nothing', [    ], true , []],
                    ['null'   , [    ], true , []],
                    ['foo'    , [    ], true , []],
                    ['foo'    , [true], true , []],
                    ['bar'    , [    ], false, ['bar' => ["The Bar format is invalid."]]],
                    ['bar'    , [true], false, ['bar' => ["The Bar format is invalid."]]],
                    ['baz'    , [    ], true , []],
                    ['baz'    , [true], false, ['baz' => ["The Baz is not a valid URL."]]],
                    ['qux'    , [    ], false, ['qux' => [
                        "The 2nd Qux (https://invalid[bar]/rebet) format is invalid.",
                    ]]],
                    ['qux'    , [true], false, ['qux' => [
                        "The 2nd Qux (https://invalid[bar]/rebet) format is invalid.",
                        "The 3rd Qux (https://invalid.local/rebet) is not a valid URL.",
                    ]]],
                ]
            ]],

            // --------------------------------------------
            // Valid::IPV4
            // --------------------------------------------
            [[
                'name'  => 'Ipv4',
                'data'  => [
                    'null'   => null,
                    'foo'    => '192.168.1.1',
                    'bar'    => '192.168.1.0/24',
                    'baz'    => '192.168.1.256',
                    'qux'    => '192.168.1.0/33',
                    'quux'   => ['192.168.1.1', '192.168.1.3', '192.168.2.0/24'],
                    'foobar' => <<<EOS
192.168.1.1
192.168.1.3

192.168.2.0/24
EOS
                    ,
                    'foobaz' => ['192.168.1.1', 'abc', '192.168.2.0/24', '192.168.2.0/34'],
                    'fooqux' => <<<EOS
192.168.1.1
abc

192.168.2.0/24
192.168.2.0/34
EOS
                    ,
                ],
                'tests' => [
                    ['nothing', [    ], true , []],
                    ['null'   , [    ], true , []],
                    ['foo'    , [    ], true , []],
                    ['bar'    , [    ], true , []],
                    ['baz'    , [    ], false, ['baz' => ["The Baz must be a valid IPv4(CIDR) address."]]],
                    ['qux'    , [    ], false, ['qux' => ["The Qux must be a valid IPv4(CIDR) address."]]],
                    ['quux'   , [    ], true , []],
                    ['foobar' , ["\n"], true , []],
                    ['foobaz' , [    ], false, ['foobaz' => [
                        "The 2nd Foobaz (abc) must be a valid IPv4(CIDR) address.",
                        "The 4th Foobaz (192.168.2.0/34) must be a valid IPv4(CIDR) address.",
                    ]]],
                    ['fooqux' , ["\n"], false, ['fooqux' => [
                        "The 2nd Fooqux (abc) must be a valid IPv4(CIDR) address.",
                        "The 4th Fooqux (192.168.2.0/34) must be a valid IPv4(CIDR) address.",
                    ]]],
                ]
            ]],

            // --------------------------------------------
            // Valid::DIGIT
            // --------------------------------------------
            [[
                'name'  => 'Digit',
                'data'  => ['null' => null, 'foo' => '123456', 'bar' => '123abc', 'baz' => '１２３', 'qux' => ['１２３', '123', 'abc', 987]],
                'tests' => [
                    ['nothing', [], true , []],
                    ['null'   , [], true , []],
                    ['foo'    , [], true , []],
                    ['bar'    , [], false, ['bar' => ["The Bar may only contain digits."]]],
                    ['baz'    , [], false, ['baz' => ["The Baz may only contain digits."]]],
                    ['qux'    , [], false, ['qux' => [
                        "The 1st Qux (１２３) may only contain digits.",
                        "The 3rd Qux (abc) may only contain digits.",
                    ]]],
                ]
            ]],

            // --------------------------------------------
            // Valid::ALPHA
            // --------------------------------------------
            [[
                'name'  => 'Alpha',
                'data'  => ['null' => null, 'foo' => 'abcDEF', 'bar' => '123abc', 'baz' => 'ＡＢＣ', 'qux' => ['ABC', '123', 'abc', 987]],
                'tests' => [
                    ['nothing', [], true , []],
                    ['null'   , [], true , []],
                    ['foo'    , [], true , []],
                    ['bar'    , [], false, ['bar' => ["The Bar may only contain letters."]]],
                    ['baz'    , [], false, ['baz' => ["The Baz may only contain letters."]]],
                    ['qux'    , [], false, ['qux' => [
                        "The 2nd Qux (123) may only contain letters.",
                        "The 4th Qux (987) may only contain letters.",
                    ]]],
                ]
            ]],

            // --------------------------------------------
            // Valid::ALPHA_DIGIT
            // --------------------------------------------
            [[
                'name'  => 'AlphaDigit',
                'data'  => ['null' => null, 'foo' => '123abcDEF', 'bar' => '123-abc', 'baz' => 'ＡＢＣ', 'qux' => ['ABC', '123', 'あいう', 'abc', 987]],
                'tests' => [
                    ['nothing', [], true , []],
                    ['null'   , [], true , []],
                    ['foo'    , [], true , []],
                    ['bar'    , [], false, ['bar' => ["The Bar may only contain letters or digits."]]],
                    ['baz'    , [], false, ['baz' => ["The Baz may only contain letters or digits."]]],
                    ['qux'    , [], false, ['qux' => [
                        "The 3rd Qux (あいう) may only contain letters or digits.",
                    ]]],
                ]
            ]],

            // --------------------------------------------
            // Valid::ALPHA_DIGIT_MARK
            // --------------------------------------------
            [[
                'name'  => 'AlphaDigitMark',
                'data'  => ['null' => null, 'foo' => '123abcDEF', 'bar' => '[123-abc]', 'baz' => 'ＡＢＣ', 'qux' => ['123-abc', '1,234', 'FOO_BAR', 123, 'foo@rebet.local']],
                'tests' => [
                    ['nothing', [     ], true , []],
                    ['null'   , [     ], true , []],
                    ['foo'    , [     ], true , []],
                    ['bar'    , [     ], true , []],
                    ['bar'    , ["-_" ], false, ['bar' => ["The Bar may only contain letters, digits or marks (include -_)."]]],
                    ['bar'    , ["-[]"], true , []],
                    ['baz'    , [     ], false, ['baz' => ["The Baz may only contain letters, digits or marks (include !\"#$%&'()*+,-./:;<=>?@[\\]^_`{|}~ )."]]],
                    ['qux'    , ["-_" ], false, ['qux' => [
                        "The 2nd Qux (1,234) may only contain letters, digits or marks (include -_).",
                        "The 5th Qux (foo@rebet.local) may only contain letters, digits or marks (include -_).",
                    ]]],
                ]
            ]],

            // --------------------------------------------
            // Valid::HIRAGANA
            // --------------------------------------------
            [[
                'name'  => 'Hiragana',
                'data'  => ['null' => null, 'foo' => 'あいうえお', 'bar' => 'abc', 'baz' => 'あいう　えお', 'qux' => ['a', 'ア', 'あ', '1']],
                'tests' => [
                    ['nothing', [     ], true , []],
                    ['null'   , [     ], true , []],
                    ['foo'    , [     ], true , []],
                    ['bar'    , [     ], false, ['bar' => ["The Bar may only contain Hiragana in Japanese."]]],
                    ['baz'    , [     ], false, ['baz' => ["The Baz may only contain Hiragana in Japanese."]]],
                    ['baz'    , ['　 '], true , []],
                    ['qux'    , [     ], false, ['qux' => [
                        "The 1st Qux (a) may only contain Hiragana in Japanese.",
                        "The 2nd Qux (ア) may only contain Hiragana in Japanese.",
                        "The 4th Qux (1) may only contain Hiragana in Japanese.",
                    ]]],
                ]
            ]],

            // --------------------------------------------
            // Valid::KANA
            // --------------------------------------------
            [[
                'name'  => 'Kana',
                'data'  => ['null' => null, 'foo' => 'アイウエオ', 'bar' => 'ｱｲｳｴｵ', 'baz' => 'abc', 'qux' => 'アイウ　エオ', 'quux' => ['a', 'ア', 'あ', '1']],
                'tests' => [
                    ['nothing', [     ], true , []],
                    ['null'   , [     ], true , []],
                    ['foo'    , [     ], true , []],
                    ['bar'    , [     ], false, ['bar' => ["The Bar may only contain full width Kana in Japanese."]]],
                    ['baz'    , [     ], false, ['baz' => ["The Baz may only contain full width Kana in Japanese."]]],
                    ['qux'    , [     ], false, ['qux' => ["The Qux may only contain full width Kana in Japanese."]]],
                    ['qux'    , ['　 '], true , []],
                    ['quux'   , [     ], false, ['quux' => [
                        "The 1st Quux (a) may only contain full width Kana in Japanese.",
                        "The 3rd Quux (あ) may only contain full width Kana in Japanese.",
                        "The 4th Quux (1) may only contain full width Kana in Japanese.",
                    ]]],
                ]
            ]],

            // --------------------------------------------
            // Valid::DEPENDENCE_CHAR
            // --------------------------------------------
            [[
                'name'  => 'DependenceChar',
                'data'  => ['null' => null, 'foo' => 'aA1$Ａアあ漢字髙①', 'bar' => 'aA1$Ａア♬あ漢字髙①', 'baz' => ['aA1', '$Ａア', '♬あ', '漢字', '髙', '①②']],
                'tests' => [
                    ['nothing', [             ], true , []],
                    ['null'   , [             ], true , []],
                    ['foo'    , [             ], true , []],
                    ['bar'    , [             ], false, ['bar' => ['The Bar must not contain platform dependent character [♬].']]],
                    ['bar'    , ['iso-2022-jp'], false, ['bar' => ['The Bar must not contain platform dependent character [♬, 髙, ①].']]],
                    ['baz'    , ['iso-2022-jp'], false, ['baz' => [
                        'The 3rd Baz (♬あ) must not contain platform dependent character [♬].',
                        'The 5th Baz (髙) must not contain platform dependent character [髙].',
                        'The 6th Baz (①②) must not contain platform dependent character [①, ②].',
                    ]]],
                ]
            ]],

            // --------------------------------------------
            // Valid::NG_WORD
            // --------------------------------------------
            // @todo implements more test cases.
            [[
                'name'  => 'NgWord',
                'data'  => [
                    'null' => null,
                    'aaa'  => 'foo bar',
                    'bbb'  => 'foo bar baz qux',
                    'ccc'  => 'foo bar b.a.z qux',
                    'ddd'  => 'foo bar.b*z qux',
                    'eee'  => 'foo bar.b** qux',
                    'fff'  => 'foo bar Ḏ*ṃɱɏ qux',
                    'ggg'  => 'てすと',
                    'hhh'  => ['foo bar', 'bar.b@z', 'ḎU**Ⓨ qux', 'はこだてストリート'],
                ],
                'tests' => [
                    ['nothing', [['baz', 'dummy']], true , []],
                    ['null'   , [['baz', 'dummy']], true , []],
                    ['aaa'    , [['baz', 'dummy']], true , []],
                    ['aaa'    , [$ng_word_file   ], true , []],
                    ['bbb'    , [['baz', 'dummy']], false, ['bbb' => ["The Bbb must not contain the word 'baz'."]]],
                    ['ccc'    , [['baz', 'dummy']], false, ['ccc' => ["The Ccc must not contain the word 'b.a.z'."]]],
                    ['ddd'    , [['baz', 'dummy']], false, ['ddd' => ["The Ddd must not contain the word 'b*z'."]]],
                    ['eee'    , [['baz', 'dummy']], true , []],
                    ['fff'    , [['baz', 'dummy']], false, ['fff' => ["The Fff must not contain the word 'Ḏ*ṃɱɏ'."]]],
                    ['ggg'    , [$ng_word_file   ], false, ['ggg' => ["The Ggg must not contain the word 'てすと'."]]],
                    ['hhh'    , [['baz', 'dummy', 'テスト']], false, ['hhh' => [
                        "The 2nd Hhh (bar.b@z) must not contain the word 'b@z'.",
                        "The 3rd Hhh (ḎU**Ⓨ qux) must not contain the word 'ḎU**Ⓨ'.",
                    ]]],
                    ['hhh'    , [['baz', 'dummy', 'テスト'], '[\p{Z}\p{P}]?'], false, ['hhh' => [
                        "The 2nd Hhh (bar.b@z) must not contain the word 'b@z'.",
                        "The 3rd Hhh (ḎU**Ⓨ qux) must not contain the word 'ḎU**Ⓨ'.",
                        "The 4th Hhh (はこだてストリート) must not contain the word 'てスト'.",
                    ]]],
                ]
            ]],

            // --------------------------------------------
            // Valid::CONTAINS
            // --------------------------------------------
            [[
                'name'  => 'Contains',
                'data'  => ['null' => null, 'foo' => '1', 'bar' => '3', 'baz' => [1, 'a', '2', 3]],
                'tests' => [
                    ['nothing', [Gender::values()], true , []],
                    ['null'   , [Gender::values()], true , []],
                    ['foo'    , [Gender::values()], true , []],
                    ['bar'    , [Gender::values()], false, ['bar' => ['The Bar must be selected from the specified list.']]],
                    ['baz'    , [Gender::values()], false, ['baz' => [
                        "The 2nd Baz must be selected from the specified list.",
                        "The 4th Baz must be selected from the specified list.",
                    ]]],
                ]
            ]],

            // --------------------------------------------
            // Valid::MIN_COUNT
            // --------------------------------------------
            [[
                'name'  => 'MinCount',
                'data'  => ['null' => null, 'foo' => [], 'bar' => '1', 'baz' => ['1', '2', '3']],
                'tests' => [
                    ['nothing', [3], false, ['nothing' => ["The Nothing must have at least 3 items."]]],
                    ['null'   , [3], false, ['null'    => ["The Null must have at least 3 items."]]],
                    ['foo'    , [1], false, ['foo'     => ["The Foo must have at least 1 item."]]],
                    ['bar'    , [3], false, ['bar'     => ["The Bar must have at least 3 items."]]],
                    ['bar'    , [1], true , []],
                    ['baz'    , [4], false, ['baz'     => ["The Baz must have at least 4 items."]]],
                    ['baz'    , [3], true , []],
                    ['baz'    , [2], true , []],
                ]
            ]],

            // --------------------------------------------
            // Valid::MAX_COUNT
            // --------------------------------------------
            [[
                'name'  => 'MaxCount',
                'data'  => ['null' => null, 'foo' => [], 'bar' => '1', 'baz' => ['1', '2', '3']],
                'tests' => [
                    ['nothing', [3], true , []],
                    ['null'   , [3], true , []],
                    ['foo'    , [1], true , []],
                    ['bar'    , [3], true , []],
                    ['bar'    , [1], true , []],
                    ['baz'    , [4], true , []],
                    ['baz'    , [3], true , []],
                    ['baz'    , [2], false, ['baz' => ["The Baz may not have more than 2 items."]]],
                    ['baz'    , [1], false, ['baz' => ["The Baz may not have more than 1 item."]]],
                ]
            ]],

            // --------------------------------------------
            // Valid::COUNT
            // --------------------------------------------
            [[
                'name'  => 'Count',
                'data'  => ['null' => null, 'foo' => [], 'bar' => '1', 'baz' => ['1', '2', '3']],
                'tests' => [
                    ['nothing', [3], false, ['nothing' => ["The Nothing must have 3 items."]]],
                    ['null'   , [3], false, ['null'    => ["The Null must have 3 items."]]],
                    ['foo'    , [3], false, ['foo'     => ["The Foo must have 3 items."]]],
                    ['bar'    , [3], false, ['bar'     => ["The Bar must have 3 items."]]],
                    ['bar'    , [1], true , []],
                    ['baz'    , [4], false, ['baz'     => ["The Baz must have 4 items."]]],
                    ['baz'    , [3], true , []],
                    ['baz'    , [2], false, ['baz'     => ["The Baz must have 2 items."]]],
                    ['baz'    , [1], false, ['baz'     => ["The Baz must have 1 item."]]],
                ]
            ]],

            // --------------------------------------------
            // Valid::UNIQUE
            // --------------------------------------------
            [[
                'name'  => 'Unique',
                'data'  => [
                    'null' => null, 'foo' => 1, 'bar' => [1, 2, 3, 'a'], 'baz' => [1, 2, 1, 'a'], 'qux' => [1, 2, 1, 'a', 'b', 'a'],
                    'quux' => [
                        ['foo' => 1, 'bar' => 1],
                        ['foo' => 2, 'bar' => 2],
                        ['foo' => 3, 'bar' => 1],
                        ['foo' => 4, 'bar' => 4],
                    ]
                ],
                'tests' => [
                    ['nothing', [     ], true , []],
                    ['null'   , [     ], true , []],
                    ['foo'    , [     ], true , []],
                    ['bar'    , [     ], true , []],
                    ['baz'    , [     ], false, ['baz'  => ["The Baz must be entered a different value. The value 1 has duplicated."]]],
                    ['qux'    , [     ], false, ['qux'  => ["The Qux must be entered a different value. The values 1, a have duplicated."]]],
                    ['quux'   , ['foo'], true , []],
                    ['quux'   , ['bar'], false, ['quux' => ["The Quux Bar must be entered a different value. The value 1 has duplicated."]]],
                ]
            ]],

            // --------------------------------------------
            // Valid::DATETIME
            // --------------------------------------------
            [[
                'name'  => 'Datetime',
                'data'  => ['null' => null, 'empty' => '', 'foo' => '2010-01-23', 'bar' => '2010-02-31', 'baz' => '2010-01-23 12:34:56', 'qux' => '2010|01|23', 'quux' => ['2010-01-23', 'abc', '2010|01|23']],
                'tests' => [
                    ['nothing', [         ], true , []],
                    ['null'   , [         ], true , []],
                    ['empty'  , [         ], true , []],
                    ['foo'    , [         ], true , []],
                    ['bar'    , [         ], false, ['bar' => ["The Bar is not a valid date time."]]],
                    ['baz'    , [         ], true , []],
                    ['qux'    , [         ], false, ['qux' => ["The Qux is not a valid date time."]]],
                    ['qux'    , ['Y\|m\|d'], true , []],
                    ['quux'   , [         ], false, ['quux' => [
                        "The 2nd Quux (abc) is not a valid date time.",
                        "The 3rd Quux (2010|01|23) is not a valid date time.",
                    ]]],
                ]
            ]],

            // --------------------------------------------
            // Valid::FUTURE_THAN
            // --------------------------------------------
            [[
                'name'  => 'FutureThan',
                'data'  => ['null' => null, 'foo' => '2100-01-01', 'bar' => '1999-01-01', 'baz' => '2010-01-23 12:34:56', 'qux' => '2010-01-23 12:34:57', 'quux' => ['2100-01-01', '1999-01-01']],
                'tests' => [
                    ['nothing', ['now'                       ], true , []],
                    ['null'   , ['now'                       ], true , []],
                    ['foo'    , ['now'                       ], true , []],
                    ['bar'    , ['now'                       ], false, ['bar' => ["The Bar must be a date future than 2010-01-23 12:34:56."]]],
                    ['bar'    , ['10 September 2000 midnight'], false, ['bar' => ["The Bar must be a date future than 2000-09-10 00:00:00."]]], // When you use strtotime() format then you should be given time format together.
                    ['bar'    , ['2000/01/01'                ], false, ['bar' => ["The Bar must be a date future than 2000/01/01."]]],
                    ['bar'    , [DateTime::now()             ], false, ['bar' => ["The Bar must be a date future than 2010-01-23 12:34:56."]]],
                    ['baz'    , ['now'                       ], false, ['baz' => ["The Baz must be a date future than 2010-01-23 12:34:56."]]],
                    ['baz'    , [':qux'                      ], false, ['baz' => ["The Baz must be a date future than Qux."]]],
                    ['qux'    , ['now'                       ], true , []],
                    ['qux'    , [':baz'                      ], true , []],
                    ['quux'   , ['now'                       ], false, ['quux' => [
                        "The 2nd Quux (1999-01-01) must be a date future than 2010-01-23 12:34:56."
                    ]]],
                    ['quux'   , [':baz'             ], false, ['quux' => [
                        "The 2nd Quux (1999-01-01) must be a date future than Baz."
                    ]]],
                ]
            ]],

            // --------------------------------------------
            // Valid::FUTURE_THAN_OR_EQUAL
            // --------------------------------------------
            [[
                'name'  => 'FutureThanOrEqual',
                'data'  => ['null' => null, 'past' => '2010-01-23 12:34:55', 'now' => '2010-01-23 12:34:56', 'future' => '2010-01-23 12:34:57', 'list' => ['2010-01-23 12:34:55', '2010-01-23 12:34:56', '2010-01-23 12:34:57']],
                'tests' => [
                    ['nothing', ['now'], true , []],
                    ['null'   , ['now'], true , []],
                    ['past'   , ['now'], false, ['past' => ["The Past must be a date future than or equal 2010-01-23 12:34:56."]]],
                    ['now'    , ['now'], true , []],
                    ['future' , ['now'], true , []],
                    ['list'   , ['now'], false, ['list' => [
                        "The 1st List (2010-01-23 12:34:55) must be a date future than or equal 2010-01-23 12:34:56."
                    ]]],
                ]
            ]],

            // --------------------------------------------
            // Valid::PAST_THAN
            // --------------------------------------------
            [[
                'name'  => 'PastThan',
                'data'  => ['null' => null, 'past' => '2010-01-23 12:34:55', 'now' => '2010-01-23 12:34:56', 'future' => '2010-01-23 12:34:57', 'list' => ['2010-01-23 12:34:55', '2010-01-23 12:34:56', '2010-01-23 12:34:57']],
                'tests' => [
                    ['nothing', ['now'], true , []],
                    ['null'   , ['now'], true , []],
                    ['past'   , ['now'], true , []],
                    ['now'    , ['now'], false, ['now'    => ["The Now must be a date past than 2010-01-23 12:34:56."]]],
                    ['future' , ['now'], false, ['future' => ["The Future must be a date past than 2010-01-23 12:34:56."]]],
                    ['list'   , ['now'], false, ['list'   => [
                        "The 2nd List (2010-01-23 12:34:56) must be a date past than 2010-01-23 12:34:56.",
                        "The 3rd List (2010-01-23 12:34:57) must be a date past than 2010-01-23 12:34:56.",
                    ]]],
                ]
            ]],

            // --------------------------------------------
            // Valid::PAST_THAN_OR_EQUAL
            // --------------------------------------------
            [[
                'name'  => 'PastThanOrEqual',
                'data'  => ['null' => null, 'past' => '2010-01-23 12:34:55', 'now' => '2010-01-23 12:34:56', 'future' => '2010-01-23 12:34:57', 'list' => ['2010-01-23 12:34:55', '2010-01-23 12:34:56', '2010-01-23 12:34:57']],
                'tests' => [
                    ['nothing', ['now'], true , []],
                    ['null'   , ['now'], true , []],
                    ['past'   , ['now'], true , []],
                    ['now'    , ['now'], true , []],
                    ['future' , ['now'], false, ['future' => ["The Future must be a date past than or equal 2010-01-23 12:34:56."]]],
                    ['list'   , ['now'], false, ['list'   => [
                        "The 3rd List (2010-01-23 12:34:57) must be a date past than or equal 2010-01-23 12:34:56.",
                    ]]],
                ]
            ]],

            // --------------------------------------------
            // Valid::MAX_AGE
            // --------------------------------------------
            [[
                'name'  => 'MaxAge',
                'data'  => ['null' => null, 'greater' => '1999-01-23', 'equals' => '2000-01-23', 'less' => '2000-01-24', 'entry_at' => '2011-01-23', 'max_age' => 10, 'list' => ['1999-01-23', '2000-01-23', '2000-01-24']],
                'tests' => [
                    ['nothing', [10                      ], true , []],
                    ['null'   , [10                      ], true , []],
                    ['greater', [10                      ], false, ['greater' => ["The age must be 10 years or younger."]]],
                    ['greater', [':max_age'              ], false, ['greater' => ["The age must be Max Age years or younger."]]],
                    ['greater', [10        , '2009-01-23'], true , []],
                    ['equals' , [10                      ], true , []],
                    ['equals' , [10        , '2011-01-23'], false, ['equals' => ["The age must be 10 years or younger as of 2011-01-23."]]],
                    ['equals' , [10        , ':entry_at' ], false, ['equals' => ["The age must be 10 years or younger as of Entry At."]]],
                    ['equals' , [':max_age', ':entry_at' ], false, ['equals' => ["The age must be Max Age years or younger as of Entry At."]]],
                    ['less'   , [10                      ], true , []],
                    ['list'   , [10                      ], false, ['list'   => [
                        "The 1st value (1999-01-23) of List must be 10 years or younger.",
                    ]]],
                    ['list'   , [10        , '2011-01-23'], false, ['list'   => [
                        "The 1st value (1999-01-23) of List must be 10 years or younger as of 2011-01-23.",
                        "The 2nd value (2000-01-23) of List must be 10 years or younger as of 2011-01-23.",
                    ]]],
                ]
            ]],

            // --------------------------------------------
            // Valid::MIN_AGE
            // --------------------------------------------
            [[
                'name'  => 'MinAge',
                'data'  => ['null' => null, 'greater' => '1999-01-23', 'equals' => '2000-01-23', 'less' => '2000-01-24', 'entry_at' => '2009-01-23', 'min_age' => 10, 'list' => ['1999-01-23', '2000-01-23', '2000-01-24']],
                'tests' => [
                    ['nothing', [10                      ], true , []],
                    ['null'   , [10                      ], true , []],
                    ['greater', [10                      ], true , []],
                    ['equals' , [10                      ], true , []],
                    ['equals' , [10        , '2009-01-23'], false, ['equals' => ["The age must be 10 years or older as of 2009-01-23."]]],
                    ['equals' , [10        , ':entry_at' ], false, ['equals' => ["The age must be 10 years or older as of Entry At."]]],
                    ['equals' , [':min_age', ':entry_at' ], false, ['equals' => ["The age must be Min Age years or older as of Entry At."]]],
                    ['less'   , [10                      ], false, ['less'   => ["The age must be 10 years or older."]]],
                    ['less'   , [':min_age'              ], false, ['less'   => ["The age must be Min Age years or older."]]],
                    ['less'   , [10        , '2010-01-24'], true , []],
                    ['list'   , [10                      ], false, ['list'   => [
                        "The 3rd value (2000-01-24) of List must be 10 years or older.",
                    ]]],
                    ['list'   , [10, '2009-01-23'        ], false, ['list'   => [
                        "The 2nd value (2000-01-23) of List must be 10 years or older as of 2009-01-23.",
                        "The 3rd value (2000-01-24) of List must be 10 years or older as of 2009-01-23.",
                    ]]],
                ]
            ]],

            // --------------------------------------------
            // Valid::SEQUENTIAL_NUMBER
            // --------------------------------------------
            [[
                'name'  => 'SequentialNumber',
                'data'  => [
                    'null' => null,
                    'foo'  => [
                        ['foo' => 1, 'bar' => 1, 'baz' => 3],
                        ['foo' => 2, 'bar' => 2, 'baz' => 5],
                        ['foo' => 3, 'bar' => 1, 'baz' => 7],
                        ['foo' => 4, 'bar' => 4, 'baz' => 9],
                    ]
                ],
                'tests' => [
                    ['nothing', ['foo'      ], true , []],
                    ['null'   , ['foo'      ], true , []],
                    ['foo'    , ['foo'      ], true , []],
                    ['foo'    , ['bar'      ], false, ['foo' => ["The Foo Bar must be sequential number."]]],
                    ['foo'    , ['baz'      ], false, ['foo' => ["The Foo Baz must be sequential number."]]],
                    ['foo'    , ['baz', 3, 2], true , []],
                ]
            ]],
        
            // --------------------------------------------
            // Valid::ACCEPTED
            // --------------------------------------------
            [[
                'name'  => 'Accepted',
                'data'  => ['null' => null, 'empty' => '', 'yes' => 'yes', 'on' => 'on', 'one_string' => '1', 'one_int' => 1, 'true_string' => 'true', 'true_bool' => true, 'array_empty' => [], 'array' => [2]],
                'tests' => [
                    ['nothing'    , [], false, ['nothing'     => ["The Nothing must be accepted."]]],
                    ['null'       , [], false, ['null'        => ["The Null must be accepted."]]],
                    ['empty'      , [], false, ['empty'       => ["The Empty must be accepted."]]],
                    ['yes'        , [], true , []],
                    ['on'         , [], true , []],
                    ['one_string' , [], true , []],
                    ['one_int'    , [], true , []],
                    ['true_string', [], true , []],
                    ['true_bool'  , [], true , []],
                    ['array_empty', [], false, ['array_empty' => ["The Array Empty must be accepted."]]],
                    ['array'      , [], false, ['array'       => ["The Array must be accepted."]]],
                ]
            ]],

            // --------------------------------------------
            // Valid::CORRELATED_REQUIRED
            // --------------------------------------------
            [[
                'name'  => 'CorrelatedRequired',
                'data'  => ['null' => null, 'empty' => '', 'zero' => 0, 'foo' => 1, 'bar' => 2, 'baz' => 3],
                'tests' => [
                    ['nothing', [['null', 'foo', 'bar', 'baz'], 2], true , []],
                    ['nothing', [['null', 'empty', 'foo'     ], 2], false, ['nothing' => ["The Null, Empty, Foo are required at least 2."]]],
                    ['nothing', [['null', 'empty', 'zero'    ], 1], true , []],
                ]
            ]],

            // --------------------------------------------
            // Valid::CORRELATED_UNIQUE
            // --------------------------------------------
            [[
                'name'  => 'CorrelatedUnique',
                'data'  => ['null' => null, 'empty' => '', 'zero' => 0, 'foo' => 1, 'bar' => 1, 'baz' => 2, 'qux' => 3, 'quux' => 3],
                'tests' => [
                    ['nothing', [['null', 'foo', 'baz', 'qux']], true , []],
                    ['nothing', [['foo', 'bar', 'qux'        ]], false, ['nothing' => ["The Foo, Bar, Qux must be entered a different value. The Foo, Bar have duplicated."]]],
                    ['nothing', [['foo', 'bar', 'qux', 'quux']], false, ['nothing' => ["The Foo, Bar, Qux, Quux must be entered a different value. The Foo, Bar, Qux, Quux have duplicated."]]],
                    ['nothing', [['null', 'empty', 'foo'     ]], false, ['nothing' => ["The Null, Empty, Foo must be entered a different value. The Null, Empty have duplicated."]]],
                    ['nothing', [['null', 'zero', 'foo'      ]], true,  []],
                ]
            ]],



        ];
    }
}
