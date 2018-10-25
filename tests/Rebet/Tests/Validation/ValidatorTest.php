<?php
namespace Rebet\Tests\Validation;

use Rebet\Tests\RebetTestCase;
use Rebet\Validation\Validator;
use Rebet\Foundation\App;
use Rebet\Validation\Valid;
use Rebet\Validation\Context;
use Rebet\Config\Config;
use org\bovigo\vfs\vfsStream;

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
        ];
    }
}
