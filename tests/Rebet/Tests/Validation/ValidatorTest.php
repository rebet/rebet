<?php
namespace Rebet\Tests\Validation;

use Rebet\Tests\RebetTestCase;
use Rebet\Validation\Validator;
use Rebet\Foundation\App;
use Rebet\Validation\Valid;
use Rebet\Validation\Context;
use Rebet\Config\Config;
use org\bovigo\vfs\vfsStream;
use Rebet\Tests\Mock\Gender;

class ValidatorTest extends RebetTestCase
{
    private $root;

    public function setup()
    {
        parent::setUp();
        $this->root = vfsStream::setup();
        vfsStream::create(
            [
                'resources' => [
                    'en' => [
                        'validation.php' => <<<'EOS'
<?php
return [
    'Regex' => [
        "{digits} The :attribute must be digits.",
    ],
    'Regex@List' => [
        "{digits} The :nth :attribute (:value) must be digits.",
    ],
    'NotRegex' => [
        "{digits} The :attribute must be not digits.",
    ],
    'NotRegex@List' => [
        "{digits} The :nth :attribute (:value) must be not digits.",
    ],
];
EOS
                    ],
                ],
            ],
            $this->root
        );
        Config::application([
            Validator::class => [
                'resources_dir' => ['vfs://root/resources'],
                'validations'   => [
                    'Ok' => function (Context $c) {
                        return true;
                    },
                    'Ng' => function (Context $c) {
                        $c->appendError("@The {$c->label} is NG.");
                        return false;
                    },
                ]
            ]
        ]);
    }

    public function test_cunstract()
    {
        $validator = new Validator([]);
        $this->assertInstanceOf(Validator::class, $validator);
    }

    /**
     * @dataProvider dataValidationMethods
     */
    public function test_validationMethods(array $data, array $rules, array $errors) : void
    {
        App::setLocale('en');
        $validator    = new Validator($data);
        $valid_data   = $validator->validate('C', $rules);
        $valid_errors = $validator->errors();
        $this->assertSame(empty($valid_errors), !is_null($valid_data));
        $this->assertSame($errors, $valid_errors);
    }

    public function dataValidationMethods() : array
    {
        App::setRoot(__DIR__.'../../../../');
        return [
            // --------------------------------------------
            // Valid::IF
            // --------------------------------------------
            [
                ['field_name' => 'value', 'other' => 'value'],
                ['field_name' => ['rule' => [
                    ['C', Valid::IF, 'other', 'value', 'then' => [['C', 'Ok']], 'else' => [['C', 'Ng']]]
                ]]],
                []
            ],
            [
                ['field_name' => 'value', 'other' => 'not-value'],
                ['field_name' => ['rule' => [
                    ['C', Valid::IF, 'other', 'value', 'then' => [['C', 'Ok']], 'else' => [['C', 'Ng']]]
                ]]],
                ['field_name' => ["The Field Name is NG."]]
            ],
            [
                ['field_name' => 'value', 'other' => 'value'],
                ['field_name' => ['rule' => [
                    ['C', Valid::IF, 'other', ':field_name', 'then' => [['C', 'Ok']], 'else' => [['C', 'Ng']]]
                ]]],
                []
            ],
            [
                ['field_name' => 'value', 'other' => 'not-value'],
                ['field_name' => ['rule' => [
                    ['C', Valid::IF, 'other', ':field_name', 'then' => [['C', 'Ok']], 'else' => [['C', 'Ng']]]
                ]]],
                ['field_name' => ["The Field Name is NG."]]
            ],
            [
                ['field_name' => 'value', 'other' => 2],
                ['field_name' => ['rule' => [
                    ['C', Valid::IF, 'other', [1, 2, 3], 'then' => [['C', 'Ok']], 'else' => [['C', 'Ng']]]
                ]]],
                []
            ],
            [
                ['field_name' => 'value', 'other' => 9],
                ['field_name' => ['rule' => [
                    ['C', Valid::IF, 'other', [1, 2, 3], 'then' => [['C', 'Ok']], 'else' => [['C', 'Ng']]]
                ]]],
                ['field_name' => ["The Field Name is NG."]]
            ],

            // --------------------------------------------
            // Valid::UNLESS
            // --------------------------------------------
            [
                ['field_name' => 'value', 'other' => 'value'],
                ['field_name' => ['rule' => [
                    ['C', Valid::UNLESS, 'other', 'value', 'then' => [['C', 'Ok']], 'else' => [['C', 'Ng']]]
                ]]],
                ['field_name' => ["The Field Name is NG."]]
            ],
            [
                ['field_name' => 'value', 'other' => 'not-value'],
                ['field_name' => ['rule' => [
                    ['C', Valid::UNLESS, 'other', 'value', 'then' => [['C', 'Ok']], 'else' => [['C', 'Ng']]]
                ]]],
                []
            ],
            [
                ['field_name' => 'value', 'other' => 'value'],
                ['field_name' => ['rule' => [
                    ['C', Valid::UNLESS, 'other', ':field_name', 'then' => [['C', 'Ok']], 'else' => [['C', 'Ng']]]
                ]]],
                ['field_name' => ["The Field Name is NG."]]
            ],
            [
                ['field_name' => 'value', 'other' => 'not-value'],
                ['field_name' => ['rule' => [
                    ['C', Valid::UNLESS, 'other', ':field_name', 'then' => [['C', 'Ok']], 'else' => [['C', 'Ng']]]
                ]]],
                []
            ],
            [
                ['field_name' => 'value', 'other' => 2],
                ['field_name' => ['rule' => [
                    ['C', Valid::UNLESS, 'other', [1, 2, 3], 'then' => [['C', 'Ok']], 'else' => [['C', 'Ng']]]
                ]]],
                ['field_name' => ["The Field Name is NG."]]
            ],
            [
                ['field_name' => 'value', 'other' => 9],
                ['field_name' => ['rule' => [
                    ['C', Valid::UNLESS, 'other', [1, 2, 3], 'then' => [['C', 'Ok']], 'else' => [['C', 'Ng']]]
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
                            $c->appendError("@The {$c->label} is not 'value'.");
                            return false;
                        }
                        return true;
                    }]
                ]]],
                ['field_name' => ["The Field Name is not 'value'."]]
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
                ['field_name' => ["The Field Name is NG."]]
            ],
            
            // --------------------------------------------
            // Valid::REQUIRED
            // --------------------------------------------
            // @todo When UploadFile
            [
                [],
                ['field_name' => ['rule' => [
                    ['C', Valid::REQUIRED]
                ]]],
                ['field_name' => ["The Field Name field is required."]]
            ],
            [
                ['field_name' => null],
                ['field_name' => ['rule' => [
                    ['C', Valid::REQUIRED]
                ]]],
                ['field_name' => ["The Field Name field is required."]]
            ],
            [
                ['field_name' => ''],
                ['field_name' => ['rule' => [
                    ['C', Valid::REQUIRED]
                ]]],
                ['field_name' => ["The Field Name field is required."]]
            ],
            [
                ['field_name' => []],
                ['field_name' => ['rule' => [
                    ['C', Valid::REQUIRED]
                ]]],
                ['field_name' => ["The Field Name field is required."]]
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
                ['field_name' => ["The Field Name field is required when Other is foo."]]
            ],
            [
                ['field_name' => 123, 'other' => 'foo'],
                ['field_name' => ['rule' => [
                    ['C', Valid::REQUIRED_IF, 'other', 'foo']
                ]]],
                []
            ],
            [
                ['field_name' => null, 'other' => 'foo'],
                ['field_name' => ['rule' => [
                    ['C', Valid::REQUIRED_IF, 'other', ['foo', 'bar', 'baz']]
                ]]],
                ['field_name' => ["The Field Name field is required when Other is in foo, bar, baz."]]
            ],
            [
                ['field_name' => null, 'other' => 'xxx'],
                ['field_name' => ['rule' => [
                    ['C', Valid::REQUIRED_IF, 'other', ['foo', 'bar', 'baz']]
                ]]],
                []
            ],
            [
                ['field_name' => 123, 'other' => 'foo', 'target' => 'foo'],
                ['field_name' => ['rule' => [
                    ['C', Valid::REQUIRED_IF, 'other', ':target']
                ]]],
                []
            ],
            [
                ['field_name' => null, 'other' => 'foo', 'target' => 'foo'],
                ['field_name' => ['rule' => [
                    ['C', Valid::REQUIRED_IF, 'other', ':target']
                ]]],
                ['field_name' => ["The Field Name field is required when Other is Target."]]
            ],
            [
                ['field_name' => null, 'other' => 'foo', 'target' => 'bar'],
                ['field_name' => ['rule' => [
                    ['C', Valid::REQUIRED_IF, 'other', ':target']
                ]]],
                []
            ],

            // --------------------------------------------
            // Valid::REQUIRED_UNLESS
            // --------------------------------------------
            [
                [],
                ['field_name' => ['rule' => [
                    ['C', Valid::REQUIRED_UNLESS, 'other', 'foo']
                ]]],
                ['field_name' => ["The Field Name field is required when Other is not foo."]]
            ],
            [
                ['other' => 'bar'],
                ['field_name' => ['rule' => [
                    ['C', Valid::REQUIRED_UNLESS, 'other', 'foo']
                ]]],
                ['field_name' => ["The Field Name field is required when Other is not foo."]]
            ],
            [
                ['other' => 'foo'],
                ['field_name' => ['rule' => [
                    ['C', Valid::REQUIRED_UNLESS, 'other', 'foo']
                ]]],
                []
            ],
            [
                ['field_name' => 123, 'other' => 'bar'],
                ['field_name' => ['rule' => [
                    ['C', Valid::REQUIRED_UNLESS, 'other', 'foo']
                ]]],
                []
            ],
            [
                ['field_name' => null, 'other' => 'foo'],
                ['field_name' => ['rule' => [
                    ['C', Valid::REQUIRED_UNLESS, 'other', ['foo', 'bar', 'baz']]
                ]]],
                []
            ],
            [
                ['field_name' => null, 'other' => 'xxx'],
                ['field_name' => ['rule' => [
                    ['C', Valid::REQUIRED_UNLESS, 'other', ['foo', 'bar', 'baz']]
                ]]],
                ['field_name' => ["The Field Name field is required when Other is not in foo, bar, baz."]]
            ],
            [
                ['field_name' => null, 'other' => 'foo', 'target' => 'foo'],
                ['field_name' => ['rule' => [
                    ['C', Valid::REQUIRED_UNLESS, 'other', ':target']
                ]]],
                []
            ],
            [
                ['field_name' => 123, 'other' => 'foo', 'target' => 'bar'],
                ['field_name' => ['rule' => [
                    ['C', Valid::REQUIRED_UNLESS, 'other', ':target']
                ]]],
                []
            ],
            [
                ['field_name' => null, 'other' => 'foo', 'target' => 'bar'],
                ['field_name' => ['rule' => [
                    ['C', Valid::REQUIRED_UNLESS, 'other', ':target']
                ]]],
                ['field_name' => ["The Field Name field is required when Other is not Target."]]
            ],

            // --------------------------------------------
            // Valid::REQUIRED_WITH
            // --------------------------------------------
            [
                ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                ['bar' => ['rule' => [
                    ['C', Valid::REQUIRED_WITH, 'baz']
                ]]],
                []
            ],
            [
                ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                ['foo' => ['rule' => [
                    ['C', Valid::REQUIRED_WITH, 'bar']
                ]]],
                ['foo' => ["The Foo field is required when Bar is present."]]
            ],
            [
                ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                ['foo' => ['rule' => [
                    ['C', Valid::REQUIRED_WITH, ['bar', 'baz']]
                ]]],
                ['foo' => ["The Foo field is required when Bar, Baz are present."]]
            ],
            [
                ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                ['foo' => ['rule' => [
                    ['C', Valid::REQUIRED_WITH, ['bar', 'baz', 'qux']]
                ]]],
                []
            ],
            [
                ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                ['foo' => ['rule' => [
                    ['C', Valid::REQUIRED_WITH, ['bar', 'baz', 'qux'], 2]
                ]]],
                ['foo' => ["The Foo field is required when Bar, Baz, Qux are present at least 2."]]
            ],
            [
                ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                ['foo' => ['rule' => [
                    ['C', Valid::REQUIRED_WITH, ['qux', 'quux'], 1]
                ]]],
                []
            ],

            // --------------------------------------------
            // Valid::REQUIRED_WITHOUT
            // --------------------------------------------
            [
                ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                ['bar' => ['rule' => [
                    ['C', Valid::REQUIRED_WITHOUT, 'qux']
                ]]],
                []
            ],
            [
                ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                ['foo' => ['rule' => [
                    ['C', Valid::REQUIRED_WITHOUT, 'qux']
                ]]],
                ['foo' => ["The Foo field is required when Qux is not present."]]
            ],
            [
                ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                ['foo' => ['rule' => [
                    ['C', Valid::REQUIRED_WITHOUT, ['qux', 'quux']]
                ]]],
                ['foo' => ["The Foo field is required when Qux, Quux are not present."]]
            ],
            [
                ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                ['foo' => ['rule' => [
                    ['C', Valid::REQUIRED_WITHOUT, ['qux', 'quux', 'bar']]
                ]]],
                []
            ],
            [
                ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                ['foo' => ['rule' => [
                    ['C', Valid::REQUIRED_WITHOUT, ['qux', 'quux', 'bar'], 2]
                ]]],
                ['foo' => ["The Foo field is required when Qux, Quux, Bar are not present at least 2."]]
            ],
            [
                ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                ['foo' => ['rule' => [
                    ['C', Valid::REQUIRED_WITHOUT, ['bar', 'baz'], 1]
                ]]],
                []
            ],

            // --------------------------------------------
            // Valid::BLANK_IF
            // --------------------------------------------
            [
                ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                ['foo' => ['rule' => [
                    ['C', Valid::BLANK_IF, 'bar', 1]
                ]]],
                []
            ],
            [
                ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                ['bar' => ['rule' => [
                    ['C', Valid::BLANK_IF, 'baz', 2]
                ]]],
                ['bar' => ["The Bar field must be blank when Baz is 2."]]
            ],
            [
                ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                ['bar' => ['rule' => [
                    ['C', Valid::BLANK_IF, 'baz', [1, 2, 3]]
                ]]],
                ['bar' => ["The Bar field must be blank when Baz is in 1, 2, 3."]]
            ],
            [
                ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                ['bar' => ['rule' => [
                    ['C', Valid::BLANK_IF, 'baz', [4, 5, 6]]
                ]]],
                []
            ],
            [
                ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                ['bar' => ['rule' => [
                    ['C', Valid::BLANK_IF, 'bar', ':baz']
                ]]],
                []
            ],
            [
                ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => 'a', 'quux' => 'a'],
                ['bar' => ['rule' => [
                    ['C', Valid::BLANK_IF, 'qux', ':quux']
                ]]],
                ['bar' => ["The Bar field must be blank when Qux is Quux."]]
            ],
            [
                ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                ['bar' => ['rule' => [
                    ['C', Valid::BLANK_IF, 'qux', ':quux']
                ]]],
                ['bar' => ["The Bar field must be blank when Qux is Quux."]]
            ],

            // --------------------------------------------
            // Valid::BLANK_UNLESS
            // --------------------------------------------
            [
                ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                ['foo' => ['rule' => [
                    ['C', Valid::BLANK_UNLESS, 'bar', 9]
                ]]],
                []
            ],
            [
                ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                ['bar' => ['rule' => [
                    ['C', Valid::BLANK_UNLESS, 'baz', 9]
                ]]],
                ['bar' => ["The Bar field must be blank when Baz is not 9."]]
            ],
            [
                ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                ['bar' => ['rule' => [
                    ['C', Valid::BLANK_UNLESS, 'baz', [7, 8, 9]]
                ]]],
                ['bar' => ["The Bar field must be blank when Baz is not in 7, 8, 9."]]
            ],
            [
                ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                ['bar' => ['rule' => [
                    ['C', Valid::BLANK_UNLESS, 'baz', [1, 2, 3]]
                ]]],
                []
            ],
            [
                ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => 1],
                ['bar' => ['rule' => [
                    ['C', Valid::BLANK_UNLESS, 'bar', ':quux']
                ]]],
                []
            ],
            [
                ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => 'a', 'quux' => 'b'],
                ['bar' => ['rule' => [
                    ['C', Valid::BLANK_UNLESS, 'qux', ':quux']
                ]]],
                ['bar' => ["The Bar field must be blank when Qux is not Quux."]]
            ],
            [
                ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                ['bar' => ['rule' => [
                    ['C', Valid::BLANK_UNLESS, 'qux', ':quux']
                ]]],
                []
            ],

            // --------------------------------------------
            // Valid::BLANK_WITH
            // --------------------------------------------
            [
                ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                ['foo' => ['rule' => [
                    ['C', Valid::BLANK_WITH, 'baz']
                ]]],
                []
            ],
            [
                ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                ['bar' => ['rule' => [
                    ['C', Valid::BLANK_WITH, 'bar']
                ]]],
                ['bar' => ["The Bar field must be blank when Bar is present."]]
            ],
            [
                ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                ['bar' => ['rule' => [
                    ['C', Valid::BLANK_WITH, ['bar', 'baz']]
                ]]],
                ['bar' => ["The Bar field must be blank when Bar, Baz are present."]]
            ],
            [
                ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                ['bar' => ['rule' => [
                    ['C', Valid::BLANK_WITH, ['bar', 'baz', 'qux']]
                ]]],
                []
            ],
            [
                ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                ['bar' => ['rule' => [
                    ['C', Valid::BLANK_WITH, ['bar', 'baz', 'qux'], 2]
                ]]],
                ['bar' => ["The Bar field must be blank when Bar, Baz, Qux are present at least 2."]]
            ],
            [
                ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                ['bar' => ['rule' => [
                    ['C', Valid::BLANK_WITH, ['qux', 'quux'], 1]
                ]]],
                []
            ],

            // --------------------------------------------
            // Valid::BLANK_WITHOUT
            // --------------------------------------------
            [
                ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                ['foo' => ['rule' => [
                    ['C', Valid::BLANK_WITHOUT, 'qux']
                ]]],
                []
            ],
            [
                ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                ['bar' => ['rule' => [
                    ['C', Valid::BLANK_WITHOUT, 'qux']
                ]]],
                ['bar' => ["The Bar field must be blank when Qux is not present."]]
            ],
            [
                ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                ['bar' => ['rule' => [
                    ['C', Valid::BLANK_WITHOUT, ['qux', 'quux']]
                ]]],
                ['bar' => ["The Bar field must be blank when Qux, Quux are not present."]]
            ],
            [
                ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                ['bar' => ['rule' => [
                    ['C', Valid::BLANK_WITHOUT, ['qux', 'quux', 'bar']]
                ]]],
                []
            ],
            [
                ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                ['bar' => ['rule' => [
                    ['C', Valid::BLANK_WITHOUT, ['qux', 'quux', 'bar'], 2]
                ]]],
                ['bar' => ["The Bar field must be blank when Qux, Quux, Bar are not present at least 2."]]
            ],
            [
                ['foo' => null, 'bar' => 1, 'baz' => 2, 'qux' => null, 'quux' => null],
                ['bar' => ['rule' => [
                    ['C', Valid::BLANK_WITHOUT, ['bar', 'baz'], 1]
                ]]],
                []
            ],

            // --------------------------------------------
            // Valid::SAME_AS
            // --------------------------------------------
            [
                [],
                ['foo' => ['rule' => [
                    ['C', Valid::SAME_AS, 1]
                ]]],
                []
            ],
            [
                ['foo' => 1, 'bar' => 2, 'baz' => 1],
                ['foo' => ['rule' => [
                    ['C', Valid::SAME_AS, 1]
                ]]],
                []
            ],
            [
                ['foo' => 1, 'bar' => 2, 'baz' => 1],
                ['foo' => ['rule' => [
                    ['C', Valid::SAME_AS, 2]
                ]]],
                ['foo' => ["The Foo and 2 must match."]]
            ],
            [
                ['foo' => 1, 'bar' => 2, 'baz' => 1],
                ['foo' => ['rule' => [
                    ['C', Valid::SAME_AS, ':bar']
                ]]],
                ['foo' => ["The Foo and Bar must match."]]
            ],
            [
                ['foo' => 1, 'bar' => 2, 'baz' => 1],
                ['foo' => ['rule' => [
                    ['C', Valid::SAME_AS, ':baz']
                ]]],
                []
            ],

            // --------------------------------------------
            // Valid::NOT_SAME_AS
            // --------------------------------------------
            [
                [],
                ['foo' => ['rule' => [
                    ['C', Valid::NOT_SAME_AS, 1]
                ]]],
                []
            ],
            [
                ['foo' => 1, 'bar' => 2, 'baz' => 1],
                ['foo' => ['rule' => [
                    ['C', Valid::NOT_SAME_AS, 2]
                ]]],
                []
            ],
            [
                ['foo' => 1, 'bar' => 2, 'baz' => 1],
                ['foo' => ['rule' => [
                    ['C', Valid::NOT_SAME_AS, 1]
                ]]],
                ['foo' => ["The Foo and 1 must not match."]]
            ],
            [
                ['foo' => 1, 'bar' => 2, 'baz' => 1],
                ['foo' => ['rule' => [
                    ['C', Valid::NOT_SAME_AS, ':baz']
                ]]],
                ['foo' => ["The Foo and Baz must not match."]]
            ],
            [
                ['foo' => 1, 'bar' => 2, 'baz' => 1],
                ['foo' => ['rule' => [
                    ['C', Valid::NOT_SAME_AS, ':bar']
                ]]],
                []
            ],

            // --------------------------------------------
            // Valid::REGEX
            // --------------------------------------------
            [
                [],
                ['foo' => ['rule' => [
                    ['C', Valid::REGEX, '/^[0-9]+$/']
                ]]],
                []
            ],
            [
                ['foo' => 1],
                ['foo' => ['rule' => [
                    ['C', Valid::REGEX, '/^[0-9]+$/']
                ]]],
                []
            ],
            [
                ['foo' => '123'],
                ['foo' => ['rule' => [
                    ['C', Valid::REGEX, '/^[0-9]+$/']
                ]]],
                []
            ],
            [
                ['foo' => 'bar'],
                ['foo' => ['rule' => [
                    ['C', Valid::REGEX, '/^[0-9]+$/']
                ]]],
                ['foo' => ["The Foo format is invalid."]]
            ],
            [
                ['foo' => 'bar'],
                ['foo' => ['rule' => [
                    ['C', Valid::REGEX, '/^[0-9]+$/', 'digits']
                ]]],
                ['foo' => ["The Foo must be digits."]]
            ],
            [
                ['foo' => ['123','456','789']],
                ['foo' => ['rule' => [
                    ['C', Valid::REGEX, '/^[0-9]+$/']
                ]]],
                []
            ],
            [
                ['foo' => ['123','abc','def']],
                ['foo' => ['rule' => [
                    ['C', Valid::REGEX, '/^[0-9]+$/']
                ]]],
                ['foo' => [
                    "The 2nd Foo (abc) format is invalid.",
                    "The 3rd Foo (def) format is invalid.",
                ]]
            ],
            [
                ['foo' => ['123','abc','def']],
                ['foo' => ['rule' => [
                    ['C', Valid::REGEX, '/^[0-9]+$/', 'digits']
                ]]],
                ['foo' => [
                    "The 2nd Foo (abc) must be digits.",
                    "The 3rd Foo (def) must be digits.",
                ]]
            ],

            // --------------------------------------------
            // Valid::NOT_REGEX
            // --------------------------------------------
            [
                [],
                ['foo' => ['rule' => [
                    ['C', Valid::NOT_REGEX, '/^[0-9]+$/']
                ]]],
                []
            ],
            [
                ['foo' => 'abc'],
                ['foo' => ['rule' => [
                    ['C', Valid::NOT_REGEX, '/^[0-9]+$/']
                ]]],
                []
            ],
            [
                ['foo' => '123'],
                ['foo' => ['rule' => [
                    ['C', Valid::NOT_REGEX, '/^[0-9]+$/']
                ]]],
                ['foo' => ["The Foo format is invalid."]]
            ],
            [
                ['foo' => '123'],
                ['foo' => ['rule' => [
                    ['C', Valid::NOT_REGEX, '/^[0-9]+$/', 'digits']
                ]]],
                ['foo' => ["The Foo must be not digits."]]
            ],
            [
                ['foo' => ['abc','def','ghi']],
                ['foo' => ['rule' => [
                    ['C', Valid::NOT_REGEX, '/^[0-9]+$/']
                ]]],
                []
            ],
            [
                ['foo' => ['abc','123','456']],
                ['foo' => ['rule' => [
                    ['C', Valid::NOT_REGEX, '/^[0-9]+$/']
                ]]],
                ['foo' => [
                    "The 2nd Foo (123) format is invalid.",
                    "The 3rd Foo (456) format is invalid.",
                ]]
            ],
            [
                ['foo' => ['abc','123','456']],
                ['foo' => ['rule' => [
                    ['C', Valid::NOT_REGEX, '/^[0-9]+$/', 'digits']
                ]]],
                ['foo' => [
                    "The 2nd Foo (123) must be not digits.",
                    "The 3rd Foo (456) must be not digits.",
                ]]
            ],

            // --------------------------------------------
            // Valid::MAX_LENGTH
            // --------------------------------------------
            [
                [],
                ['foo' => ['rule' => [
                    ['C', Valid::MAX_LENGTH, 3]
                ]]],
                []
            ],
            [
                ['foo' => 'abc'],
                ['foo' => ['rule' => [
                    ['C', Valid::MAX_LENGTH, 3]
                ]]],
                []
            ],
            [
                ['foo' => 'abcd'],
                ['foo' => ['rule' => [
                    ['C', Valid::MAX_LENGTH, 3]
                ]]],
                ['foo' => ["The Foo may not be greater than 3 characters."]]
            ],
            [
                ['foo' => ['1234','1','123','12345']],
                ['foo' => ['rule' => [
                    ['C', Valid::MAX_LENGTH, 3]
                ]]],
                ['foo' => [
                    "The 1st Foo (1234) may not be greater than 3 characters.",
                    "The 4th Foo (12345) may not be greater than 3 characters.",
                ]]
            ],
            
            // --------------------------------------------
            // Valid::MIN_LENGTH
            // --------------------------------------------
            [
                [],
                ['foo' => ['rule' => [
                    ['C', Valid::MIN_LENGTH, 3]
                ]]],
                []
            ],
            [
                ['foo' => 'abc'],
                ['foo' => ['rule' => [
                    ['C', Valid::MIN_LENGTH, 3]
                ]]],
                []
            ],
            [
                ['foo' => 'ab'],
                ['foo' => ['rule' => [
                    ['C', Valid::MIN_LENGTH, 3]
                ]]],
                ['foo' => ["The Foo must be at least 3 characters."]]
            ],
            [
                ['foo' => ['1234', '1', '123']],
                ['foo' => ['rule' => [
                    ['C', Valid::MIN_LENGTH, 3]
                ]]],
                ['foo' => [
                    "The 2nd Foo (1) must be at least 3 characters.",
                ]]
            ],
            
            // --------------------------------------------
            // Valid::LENGTH
            // --------------------------------------------
            [
                [],
                ['foo' => ['rule' => [
                    ['C', Valid::LENGTH, 3]
                ]]],
                []
            ],
            [
                ['foo' => 'abc'],
                ['foo' => ['rule' => [
                    ['C', Valid::LENGTH, 3]
                ]]],
                []
            ],
            [
                ['foo' => 'ab'],
                ['foo' => ['rule' => [
                    ['C', Valid::LENGTH, 3]
                ]]],
                ['foo' => ["The Foo must be 3 characters."]]
            ],
            [
                ['foo' => ['12', '1', '123']],
                ['foo' => ['rule' => [
                    ['C', Valid::LENGTH, 3]
                ]]],
                ['foo' => [
                    "The 1st Foo (12) must be 3 characters.",
                    "The 2nd Foo (1) must be 3 characters.",
                ]]
            ],
            
            // --------------------------------------------
            // Valid::NUMBER
            // --------------------------------------------
            [
                [],
                ['foo' => ['rule' => [
                    ['C', Valid::NUMBER]
                ]]],
                []
            ],
            [
                ['foo' => '123'],
                ['foo' => ['rule' => [
                    ['C', Valid::NUMBER]
                ]]],
                []
            ],
            [
                ['foo' => 'abc'],
                ['foo' => ['rule' => [
                    ['C', Valid::NUMBER]
                ]]],
                ['foo' => ["The Foo must be number."]]
            ],
            [
                ['foo' => ['+123.4', '-1234', '1.234', '123']],
                ['foo' => ['rule' => [
                    ['C', Valid::NUMBER]
                ]]],
                []
            ],
            [
                ['foo' => ['+123.4', '-1,234', '1.234']],
                ['foo' => ['rule' => [
                    ['C', Valid::NUMBER]
                ]]],
                ['foo' => ["The 2nd Foo (-1,234) must be number."]]
            ],
            
            // --------------------------------------------
            // Valid::INTEGER
            // --------------------------------------------
            [
                [],
                ['foo' => ['rule' => [
                    ['C', Valid::INTEGER]
                ]]],
                []
            ],
            [
                ['foo' => '123'],
                ['foo' => ['rule' => [
                    ['C', Valid::INTEGER]
                ]]],
                []
            ],
            [
                ['foo' => 'abc'],
                ['foo' => ['rule' => [
                    ['C', Valid::INTEGER]
                ]]],
                ['foo' => ["The Foo must be integer."]]
            ],
            [
                ['foo' => ['+123', '-1234', '+1234']],
                ['foo' => ['rule' => [
                    ['C', Valid::INTEGER]
                ]]],
                []
            ],
            [
                ['foo' => ['+123.4', '123', 'abc']],
                ['foo' => ['rule' => [
                    ['C', Valid::INTEGER]
                ]]],
                ['foo' => [
                    "The 1st Foo (+123.4) must be integer.",
                    "The 3rd Foo (abc) must be integer.",
                ]]
            ],
            
            // --------------------------------------------
            // Valid::FLOAT
            // --------------------------------------------
            [
                [],
                ['foo' => ['rule' => [
                    ['C', Valid::FLOAT, 2]
                ]]],
                []
            ],
            [
                ['foo' => '123'],
                ['foo' => ['rule' => [
                    ['C', Valid::FLOAT, 2]
                ]]],
                []
            ],
            [
                ['foo' => '123.12'],
                ['foo' => ['rule' => [
                    ['C', Valid::FLOAT, 2]
                ]]],
                []
            ],
            [
                ['foo' => '123.123'],
                ['foo' => ['rule' => [
                    ['C', Valid::FLOAT, 2]
                ]]],
                ['foo' => ["The Foo must be real number (up to 2 decimal places)."]]
            ],
            [
                ['foo' => 'abc'],
                ['foo' => ['rule' => [
                    ['C', Valid::FLOAT, 2]
                ]]],
                ['foo' => ["The Foo must be real number (up to 2 decimal places)."]]
            ],
            [
                ['foo' => ['+123', '-123.4', '+12.34']],
                ['foo' => ['rule' => [
                    ['C', Valid::FLOAT, 2]
                ]]],
                []
            ],
            [
                ['foo' => ['+123.4', '123.230', 'abc']],
                ['foo' => ['rule' => [
                    ['C', Valid::FLOAT, 2]
                ]]],
                ['foo' => [
                    "The 2nd Foo (123.230) must be real number (up to 2 decimal places).",
                    "The 3rd Foo (abc) must be real number (up to 2 decimal places).",
                ]]
            ],
            
            // --------------------------------------------
            // Valid::MAX_NUMBER
            // --------------------------------------------
            [
                [],
                ['foo' => ['rule' => [
                    ['C', Valid::MAX_NUMBER, 10]
                ]]],
                []
            ],
            [
                ['foo' => '10'],
                ['foo' => ['rule' => [
                    ['C', Valid::MAX_NUMBER, 10]
                ]]],
                []
            ],
            [
                ['foo' => '-11'],
                ['foo' => ['rule' => [
                    ['C', Valid::MAX_NUMBER, 10]
                ]]],
                []
            ],
            [
                ['foo' => '11'],
                ['foo' => ['rule' => [
                    ['C', Valid::MAX_NUMBER, 10]
                ]]],
                ['foo' => ["The Foo may not be greater than 10."]]
            ],
            [
                ['foo' => '10.1'],
                ['foo' => ['rule' => [
                    ['C', Valid::MAX_NUMBER, 10]
                ]]],
                ['foo' => ["The Foo must be integer."]]
            ],
            [
                ['foo' => '10.1'],
                ['foo' => ['rule' => [
                    ['C', Valid::MAX_NUMBER, 10, 1]
                ]]],
                ['foo' => ["The Foo may not be greater than 10."]]
            ],
            [
                ['foo' => 'abc'],
                ['foo' => ['rule' => [
                    ['C', Valid::MAX_NUMBER, 10]
                ]]],
                ['foo' => ["The Foo must be integer."]]
            ],
            [
                ['foo' => 'abc'],
                ['foo' => ['rule' => [
                    ['C', Valid::MAX_NUMBER, 10, 1]
                ]]],
                ['foo' => ["The Foo must be real number (up to 1 decimal places)."]]
            ],
            [
                ['foo' => ['abc', '10', '2', 123, '3.5']],
                ['foo' => ['rule' => [
                    ['C', Valid::MAX_NUMBER, 10]
                ]]],
                ['foo' => [
                    "The 1st Foo (abc) must be integer.",
                    "The 5th Foo (3.5) must be integer.",
                    "The 4th Foo (123) may not be greater than 10.",
                ]]
            ],
            [
                ['foo' => ['abc', '10', '2', 123, '3.5']],
                ['foo' => ['rule' => [
                    ['C', Valid::MAX_NUMBER, 10, 1]
                ]]],
                ['foo' => [
                    "The 1st Foo (abc) must be real number (up to 1 decimal places).",
                    "The 4th Foo (123) may not be greater than 10.",
                ]]
            ],
            
            // --------------------------------------------
            // Valid::MIN_NUMBER
            // --------------------------------------------
            [
                [],
                ['foo' => ['rule' => [
                    ['C', Valid::MIN_NUMBER, 10]
                ]]],
                []
            ],
            [
                ['foo' => '10'],
                ['foo' => ['rule' => [
                    ['C', Valid::MIN_NUMBER, 10]
                ]]],
                []
            ],
            [
                ['foo' => '-11'],
                ['foo' => ['rule' => [
                    ['C', Valid::MIN_NUMBER, 10]
                ]]],
                ['foo' => ["The Foo must be at least 10."]]
            ],
            [
                ['foo' => '11'],
                ['foo' => ['rule' => [
                    ['C', Valid::MIN_NUMBER, 10]
                ]]],
                []
            ],
            [
                ['foo' => '10.1'],
                ['foo' => ['rule' => [
                    ['C', Valid::MIN_NUMBER, 10]
                ]]],
                ['foo' => ["The Foo must be integer."]]
            ],
            [
                ['foo' => '10.1'],
                ['foo' => ['rule' => [
                    ['C', Valid::MIN_NUMBER, 10, 1]
                ]]],
                []
            ],
            [
                ['foo' => 'abc'],
                ['foo' => ['rule' => [
                    ['C', Valid::MIN_NUMBER, 10]
                ]]],
                ['foo' => ["The Foo must be integer."]]
            ],
            [
                ['foo' => 'abc'],
                ['foo' => ['rule' => [
                    ['C', Valid::MIN_NUMBER, 10, 1]
                ]]],
                ['foo' => ["The Foo must be real number (up to 1 decimal places)."]]
            ],
            [
                ['foo' => ['abc', '10', '2', 123, '3.5']],
                ['foo' => ['rule' => [
                    ['C', Valid::MIN_NUMBER, 10]
                ]]],
                ['foo' => [
                    "The 1st Foo (abc) must be integer.",
                    "The 5th Foo (3.5) must be integer.",
                    "The 3rd Foo (2) must be at least 10.",
                ]]
            ],
            [
                ['foo' => ['abc', '10', '2', 123, '3.5']],
                ['foo' => ['rule' => [
                    ['C', Valid::MIN_NUMBER, 10, 1]
                ]]],
                ['foo' => [
                    "The 1st Foo (abc) must be real number (up to 1 decimal places).",
                    "The 3rd Foo (2) must be at least 10.",
                    "The 5th Foo (3.5) must be at least 10.",
                ]]
            ],

            // --------------------------------------------
            // Valid::EMAIL
            // --------------------------------------------
            [
                [],
                ['foo' => ['rule' => [
                    ['C', Valid::EMAIL]
                ]]],
                []
            ],
            [
                ['foo' => 'foo@rebet.com'],
                ['foo' => ['rule' => [
                    ['C', Valid::EMAIL]
                ]]],
                []
            ],
            [
                ['foo' => '.foo@rebet.com'],
                ['foo' => ['rule' => [
                    ['C', Valid::EMAIL]
                ]]],
                ['foo' => ["The Foo must be a valid email address."]]
            ],
            [
                ['foo' => '.foo@rebet.com'],
                ['foo' => ['rule' => [
                    ['C', Valid::EMAIL, false]
                ]]],
                []
            ],
            [
                ['foo' => ['foo@rebet.com', '.bar@rebet.com', 'abc', 'foo.bar@rebet.com', 'foo..baz@rebet.com']],
                ['foo' => ['rule' => [
                    ['C', Valid::EMAIL]
                ]]],
                ['foo' => [
                    "The 2nd Foo (.bar@rebet.com) must be a valid email address.",
                    "The 3rd Foo (abc) must be a valid email address.",
                    "The 5th Foo (foo..baz@rebet.com) must be a valid email address.",
                ]]
            ],
            [
                ['foo' => ['foo@rebet.com', '.bar@rebet.com', 'abc', 'foo.bar@rebet.com', 'foo..baz@rebet.com']],
                ['foo' => ['rule' => [
                    ['C', Valid::EMAIL, false]
                ]]],
                ['foo' => [
                    "The 3rd Foo (abc) must be a valid email address.",
                ]]
            ],

            // --------------------------------------------
            // Valid::URL
            // --------------------------------------------
            [
                [],
                ['foo' => ['rule' => [
                    ['C', Valid::URL]
                ]]],
                []
            ],
            [
                ['foo' => 'https://github.com/rebet/rebet'],
                ['foo' => ['rule' => [
                    ['C', Valid::URL]
                ]]],
                []
            ],
            [
                ['foo' => 'https://github.com/rebet/rebet'],
                ['foo' => ['rule' => [
                    ['C', Valid::URL, true]
                ]]],
                []
            ],
            [
                ['foo' => 'https://invalid[foo]/rebet'],
                ['foo' => ['rule' => [
                    ['C', Valid::URL]
                ]]],
                ['foo' => ["The Foo format is invalid."]]
            ],
            [
                ['foo' => 'https://invalid.local/rebet'],
                ['foo' => ['rule' => [
                    ['C', Valid::URL, true]
                ]]],
                ['foo' => ["The Foo is not a valid URL."]]
            ],
            [
                ['foo' => ['https://github.com/rebet/rebet', 'https://invalid[foo]/rebet']],
                ['foo' => ['rule' => [
                    ['C', Valid::URL]
                ]]],
                ['foo' => ["The 2nd Foo (https://invalid[foo]/rebet) format is invalid."]]
            ],
            [
                ['foo' => ['https://github.com/rebet/rebet', 'https://invalid.local/rebet']],
                ['foo' => ['rule' => [
                    ['C', Valid::URL, true]
                ]]],
                ['foo' => ["The 2nd Foo (https://invalid.local/rebet) is not a valid URL."]]
            ],

            // --------------------------------------------
            // Valid::IPV4
            // --------------------------------------------
            [
                [],
                ['foo' => ['rule' => [
                    ['C', Valid::IPV4]
                ]]],
                []
            ],
            [
                ['foo' => '192.168.1.1'],
                ['foo' => ['rule' => [
                    ['C', Valid::IPV4]
                ]]],
                []
            ],
            [
                ['foo' => '192.168.1.0/24'],
                ['foo' => ['rule' => [
                    ['C', Valid::IPV4]
                ]]],
                []
            ],
            [
                ['foo' => '192.168.1.256'],
                ['foo' => ['rule' => [
                    ['C', Valid::IPV4]
                ]]],
                ['foo' => ["The Foo must be a valid IPv4(CIDR) address."]]
            ],
            [
                ['foo' => '192.168.1.0/33'],
                ['foo' => ['rule' => [
                    ['C', Valid::IPV4]
                ]]],
                ['foo' => ["The Foo must be a valid IPv4(CIDR) address."]]
            ],
            [
                ['foo' => ['192.168.1.1', '192.168.1.3', '192.168.2.0/24']],
                ['foo' => ['rule' => [
                    ['C', Valid::IPV4]
                ]]],
                []
            ],
            [
                ['foo' => <<<EOS
192.168.1.1
192.168.1.3

192.168.2.0/24
EOS
                ],
                ['foo' => ['rule' => [
                    ['C', Valid::IPV4, "\n"]
                ]]],
                []
            ],
            [
                ['foo' => ['192.168.1.1', 'abc', '192.168.2.0/24','192.168.2.0/34']],
                ['foo' => ['rule' => [
                    ['C', Valid::IPV4]
                ]]],
                ['foo' => [
                    "The 2nd Foo (abc) must be a valid IPv4(CIDR) address.",
                    "The 4th Foo (192.168.2.0/34) must be a valid IPv4(CIDR) address.",
                ]]
            ],
            [
                ['foo' => <<<EOS
192.168.1.1
abc

192.168.2.0/24
192.168.2.0/34
EOS
                ],
                ['foo' => ['rule' => [
                    ['C', Valid::IPV4, "\n"]
                ]]],
                ['foo' => [
                    "The 2nd Foo (abc) must be a valid IPv4(CIDR) address.",
                    "The 4th Foo (192.168.2.0/34) must be a valid IPv4(CIDR) address.",
                ]]
            ],

            // --------------------------------------------
            // Valid::DIGIT
            // --------------------------------------------
            [
                [],
                ['foo' => ['rule' => [
                    ['C', Valid::DIGIT]
                ]]],
                []
            ],
            [
                ['foo' => '123456'],
                ['foo' => ['rule' => [
                    ['C', Valid::DIGIT]
                ]]],
                []
            ],
            [
                ['foo' => '123abc'],
                ['foo' => ['rule' => [
                    ['C', Valid::DIGIT]
                ]]],
                ['foo' => ["The Foo may only contain digits."]]
            ],
            [
                ['foo' => '１２３'],
                ['foo' => ['rule' => [
                    ['C', Valid::DIGIT]
                ]]],
                ['foo' => ["The Foo may only contain digits."]]
            ],
            [
                ['foo' => ['１２３', '123', 'abc', 987]],
                ['foo' => ['rule' => [
                    ['C', Valid::DIGIT]
                ]]],
                ['foo' => [
                    "The 1st Foo (１２３) may only contain digits.",
                    "The 3rd Foo (abc) may only contain digits.",
                ]]
            ],

            // --------------------------------------------
            // Valid::ALPHA
            // --------------------------------------------
            [
                [],
                ['foo' => ['rule' => [
                    ['C', Valid::ALPHA]
                ]]],
                []
            ],
            [
                ['foo' => 'abcDEF'],
                ['foo' => ['rule' => [
                    ['C', Valid::ALPHA]
                ]]],
                []
            ],
            [
                ['foo' => '123abc'],
                ['foo' => ['rule' => [
                    ['C', Valid::ALPHA]
                ]]],
                ['foo' => ["The Foo may only contain letters."]]
            ],
            [
                ['foo' => 'ＡＢＣ'],
                ['foo' => ['rule' => [
                    ['C', Valid::ALPHA]
                ]]],
                ['foo' => ["The Foo may only contain letters."]]
            ],
            [
                ['foo' => ['ABC', '123', 'abc', 987]],
                ['foo' => ['rule' => [
                    ['C', Valid::ALPHA]
                ]]],
                ['foo' => [
                    "The 2nd Foo (123) may only contain letters.",
                    "The 4th Foo (987) may only contain letters.",
                ]]
            ],

            // --------------------------------------------
            // Valid::ALPHA_DIGIT
            // --------------------------------------------
            [
                [],
                ['foo' => ['rule' => [
                    ['C', Valid::ALPHA_DIGIT]
                ]]],
                []
            ],
            [
                ['foo' => '123abcDEF'],
                ['foo' => ['rule' => [
                    ['C', Valid::ALPHA_DIGIT]
                ]]],
                []
            ],
            [
                ['foo' => '123-abc'],
                ['foo' => ['rule' => [
                    ['C', Valid::ALPHA_DIGIT]
                ]]],
                ['foo' => ["The Foo may only contain letters or digits."]]
            ],
            [
                ['foo' => 'ＡＢＣ'],
                ['foo' => ['rule' => [
                    ['C', Valid::ALPHA_DIGIT]
                ]]],
                ['foo' => ["The Foo may only contain letters or digits."]]
            ],
            [
                ['foo' => ['ABC', '123', 'あいう', 'abc', 987]],
                ['foo' => ['rule' => [
                    ['C', Valid::ALPHA_DIGIT]
                ]]],
                ['foo' => [
                    "The 3rd Foo (あいう) may only contain letters or digits.",
                ]]
            ],

            // --------------------------------------------
            // Valid::ALPHA_DIGIT_MARK
            // --------------------------------------------
            [
                [],
                ['foo' => ['rule' => [
                    ['C', Valid::ALPHA_DIGIT_MARK]
                ]]],
                []
            ],
            [
                ['foo' => 'abcDEF'],
                ['foo' => ['rule' => [
                    ['C', Valid::ALPHA_DIGIT_MARK]
                ]]],
                []
            ],
            [
                ['foo' => '[123-abc]'],
                ['foo' => ['rule' => [
                    ['C', Valid::ALPHA_DIGIT_MARK]
                ]]],
                []
            ],
            [
                ['foo' => 'ＡＢＣ'],
                ['foo' => ['rule' => [
                    ['C', Valid::ALPHA_DIGIT_MARK]
                ]]],
                ['foo' => ["The Foo may only contain letters, digits or marks (include !\"#$%&'()*+,-./:;<=>?@[\\]^_`{|}~ )."]]
            ],
            [
                ['foo' => '[123-abc]'],
                ['foo' => ['rule' => [
                    ['C', Valid::ALPHA_DIGIT_MARK, "-_"]
                ]]],
                ['foo' => ["The Foo may only contain letters, digits or marks (include -_)."]]
            ],
            [
                ['foo' => '[123-abc]'],
                ['foo' => ['rule' => [
                    ['C', Valid::ALPHA_DIGIT_MARK, "[-]"]
                ]]],
                []
            ],
            [
                ['foo' => ['123-abc', '1,234', 'FOO_BAR', 123, 'foo@rebet.com']],
                ['foo' => ['rule' => [
                    ['C', Valid::ALPHA_DIGIT_MARK, "-_"]
                ]]],
                ['foo' => [
                    "The 2nd Foo (1,234) may only contain letters, digits or marks (include -_).",
                    "The 5th Foo (foo@rebet.com) may only contain letters, digits or marks (include -_).",
                ]]
            ],

            // --------------------------------------------
            // Valid::HIRAGANA
            // --------------------------------------------
            [
                [],
                ['foo' => ['rule' => [
                    ['C', Valid::HIRAGANA]
                ]]],
                []
            ],
            [
                ['foo' => 'あいうえお'],
                ['foo' => ['rule' => [
                    ['C', Valid::HIRAGANA]
                ]]],
                []
            ],
            [
                ['foo' => 'abc'],
                ['foo' => ['rule' => [
                    ['C', Valid::HIRAGANA]
                ]]],
                ['foo' => ["The Foo may only contain Hiragana in Japanese."]]
            ],
            [
                ['foo' => 'あいう　えお'],
                ['foo' => ['rule' => [
                    ['C', Valid::HIRAGANA]
                ]]],
                ['foo' => ["The Foo may only contain Hiragana in Japanese."]]
            ],
            [
                ['foo' => 'あいう　えお'],
                ['foo' => ['rule' => [
                    ['C', Valid::HIRAGANA, '　 ']
                ]]],
                []
            ],
            [
                ['foo' => ['a', 'ア', 'あ','1']],
                ['foo' => ['rule' => [
                    ['C', Valid::HIRAGANA]
                ]]],
                ['foo' => [
                    "The 1st Foo (a) may only contain Hiragana in Japanese.",
                    "The 2nd Foo (ア) may only contain Hiragana in Japanese.",
                    "The 4th Foo (1) may only contain Hiragana in Japanese.",
                ]]
            ],

            // --------------------------------------------
            // Valid::KANA
            // --------------------------------------------
            [
                [],
                ['foo' => ['rule' => [
                    ['C', Valid::KANA]
                ]]],
                []
            ],
            [
                ['foo' => 'アイウエオ'],
                ['foo' => ['rule' => [
                    ['C', Valid::KANA]
                ]]],
                []
            ],
            [
                ['foo' => 'ｱｲｳｴｵ'],
                ['foo' => ['rule' => [
                    ['C', Valid::KANA]
                ]]],
                ['foo' => ["The Foo may only contain full width Kana in Japanese."]]
            ],
            [
                ['foo' => 'abc'],
                ['foo' => ['rule' => [
                    ['C', Valid::KANA]
                ]]],
                ['foo' => ["The Foo may only contain full width Kana in Japanese."]]
            ],
            [
                ['foo' => 'アイウ　エオ'],
                ['foo' => ['rule' => [
                    ['C', Valid::KANA]
                ]]],
                ['foo' => ["The Foo may only contain full width Kana in Japanese."]]
            ],
            [
                ['foo' => 'アイウ　エオ'],
                ['foo' => ['rule' => [
                    ['C', Valid::KANA, '　 ']
                ]]],
                []
            ],
            [
                ['foo' => ['a', 'ア', 'あ','1']],
                ['foo' => ['rule' => [
                    ['C', Valid::KANA]
                ]]],
                ['foo' => [
                    "The 1st Foo (a) may only contain full width Kana in Japanese.",
                    "The 3rd Foo (あ) may only contain full width Kana in Japanese.",
                    "The 4th Foo (1) may only contain full width Kana in Japanese.",
                ]]
            ],

            // --------------------------------------------
            // Valid::DEPENDENCE_CHAR
            // --------------------------------------------
            [
                [],
                ['foo' => ['rule' => [
                    ['C', Valid::DEPENDENCE_CHAR]
                ]]],
                []
            ],
            [
                ['foo' => 'aA1$Ａアあ漢字髙①'],
                ['foo' => ['rule' => [
                    ['C', Valid::DEPENDENCE_CHAR]
                ]]],
                []
            ],
            [
                ['foo' => 'aA1$Ａア♬あ漢字髙①'],
                ['foo' => ['rule' => [
                    ['C', Valid::DEPENDENCE_CHAR]
                ]]],
                ['foo' => ['The Foo must not contain platform dependent character [♬].']]
            ],
            [
                ['foo' => 'aA1$Ａア♬あ漢字髙①'],
                ['foo' => ['rule' => [
                    ['C', Valid::DEPENDENCE_CHAR, 'iso-2022-jp']
                ]]],
                ['foo' => ['The Foo must not contain platform dependent character [♬, 髙, ①].']]
            ],
            [
                ['foo' => ['aA1','$Ａア','♬あ','漢字','髙','①②']],
                ['foo' => ['rule' => [
                    ['C', Valid::DEPENDENCE_CHAR, 'iso-2022-jp']
                ]]],
                ['foo' => [
                    'The 3rd Foo (♬あ) must not contain platform dependent character [♬].',
                    'The 5th Foo (髙) must not contain platform dependent character [髙].',
                    'The 6th Foo (①②) must not contain platform dependent character [①, ②].',
                ]]
            ],

            // --------------------------------------------
            // Valid::NG_WORD
            // --------------------------------------------
            // @todo implements more test cases.
            [
                [],
                ['foo' => ['rule' => [
                    ['C', Valid::NG_WORD, ['baz', 'dummy']]
                ]]],
                []
            ],
            [
                ['foo' => 'foo bar'],
                ['foo' => ['rule' => [
                    ['C', Valid::NG_WORD, ['baz', 'dummy']]
                ]]],
                []
            ],
            [
                ['foo' => 'foo bar'],
                ['foo' => ['rule' => [
                    ['C', Valid::NG_WORD, App::path('/resources/ng_word.txt')]
                ]]],
                []
            ],
            [
                ['foo' => 'foo bar baz qux'],
                ['foo' => ['rule' => [
                    ['C', Valid::NG_WORD, ['baz', 'dummy']]
                ]]],
                ['foo' => ["The Foo must not contain the word 'baz'."]]
            ],
            [
                ['foo' => 'foo bar b.a.z qux'],
                ['foo' => ['rule' => [
                    ['C', Valid::NG_WORD, ['baz', 'dummy']]
                ]]],
                ['foo' => ["The Foo must not contain the word 'b.a.z'."]]
            ],
            [
                ['foo' => 'foo bar.b*z qux'],
                ['foo' => ['rule' => [
                    ['C', Valid::NG_WORD, ['baz', 'dummy']]
                ]]],
                ['foo' => ["The Foo must not contain the word 'b*z'."]]
            ],
            [
                ['foo' => 'foo bar.b** qux'],
                ['foo' => ['rule' => [
                    ['C', Valid::NG_WORD, ['baz', 'dummy']]
                ]]],
                []
            ],
            [
                ['foo' => 'foo bar Ḏ*ṃɱɏ qux'],
                ['foo' => ['rule' => [
                    ['C', Valid::NG_WORD, ['baz', 'dummy']]
                ]]],
                ['foo' => ["The Foo must not contain the word 'Ḏ*ṃɱɏ'."]]
            ],
            [
                ['foo' => 'てすと'],
                ['foo' => ['rule' => [
                    ['C', Valid::NG_WORD, App::path('/resources/ng_word.txt')]
                ]]],
                ['foo' => ["The Foo must not contain the word 'てすと'."]]
            ],
            [
                ['foo' => ['foo bar', 'bar.b@z', 'ḎU**Ⓨ qux', 'はこだてストリート']],
                ['foo' => ['rule' => [
                    ['C', Valid::NG_WORD, ['baz', 'dummy', 'テスト']]
                ]]],
                ['foo' => [
                    "The 2nd Foo (bar.b@z) must not contain the word 'b@z'.",
                    "The 3rd Foo (ḎU**Ⓨ qux) must not contain the word 'ḎU**Ⓨ'.",
                ]]
            ],
            [
                ['foo' => ['foo bar', 'bar.b@z', 'ḎU**Ⓨ qux', 'はこだてストリート']],
                ['foo' => ['rule' => [
                    ['C', Valid::NG_WORD, ['baz', 'dummy', 'テスト'], '[\p{Z}\p{P}]?']
                ]]],
                ['foo' => [
                    "The 2nd Foo (bar.b@z) must not contain the word 'b@z'.",
                    "The 3rd Foo (ḎU**Ⓨ qux) must not contain the word 'ḎU**Ⓨ'.",
                    "The 4th Foo (はこだてストリート) must not contain the word 'てスト'.",
                ]]
            ],

            // --------------------------------------------
            // Valid::CONTAINS
            // --------------------------------------------
            [
                [],
                ['foo' => ['rule' => [
                    ['C', Valid::CONTAINS, Gender::values()]
                ]]],
                []
            ],
            [
                ['foo' => '1'],
                ['foo' => ['rule' => [
                    ['C', Valid::CONTAINS, Gender::values()]
                ]]],
                []
            ],
            [
                ['foo' => '3'],
                ['foo' => ['rule' => [
                    ['C', Valid::CONTAINS, Gender::values()]
                ]]],
                ['foo' => ["The Foo must be selected from the specified list."]]
            ],
            [
                ['foo' => [1, 'a', '2', 3]],
                ['foo' => ['rule' => [
                    ['C', Valid::CONTAINS, Gender::values()]
                ]]],
                ['foo' => [
                    "The 2nd Foo must be selected from the specified list.",
                    "The 4th Foo must be selected from the specified list.",
                ]]
            ],



        ];
    }
}
