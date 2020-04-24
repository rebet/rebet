<?php
namespace Rebet\Tests\Validation;

use Rebet\Config\Config;
use Rebet\DateTime\DateTime;
use Rebet\Application\App;
use Rebet\Http\UploadedFile;
use Rebet\Tests\Mock\Enum\Gender;
use Rebet\Tests\Mock\Validation\BarValidation;
use Rebet\Tests\Mock\Validation\FooValidation;
use Rebet\Tests\RebetTestCase;
use Rebet\Validation\BuiltinValidations;
use Rebet\Validation\Context;
use Rebet\Validation\Valid;
use Rebet\Validation\Validator;
use Rebet\Validation\ValidData;

class ValidatorTest extends RebetTestCase
{
    private $root;

    public function setup()
    {
        parent::setUp();
        DateTime::setTestNow('2010-01-23 12:34:56');

        Config::application([
            BuiltinValidations::class => [
                'customs' => [
                    'Ok' => function (Context $c) {
                        return true;
                    },
                    'Ng' => function (Context $c, ?string $message = null) {
                        $c->appendError($message ?? "@The {$c->label} is NG.");
                        return false;
                    },
                ]
            ]
        ]);
    }

    public function test_cunstract()
    {
        $this->assertInstanceOf(Validator::class, new Validator([]));
    }

    public function dataValidationInvoke() : array
    {
        $this->setUp();
        $image_72x72_png = new UploadedFile(App::path('/resources/image/72x72.png'), '72x72.png');
        return [
            // Valid::IF
            [['target' => 1], ['C', Valid::IF, 'target', 1, 'then' => [['C', 'Ok']], 'else' => [['C', 'Ng']]], true ],
            [['target' => 2], ['C', Valid::IF, 'target', 1, 'then' => [['C', 'Ok']], 'else' => [['C', 'Ng']]], false],
            // Valid::UNLESS
            [['target' => 1], ['C', Valid::UNLESS, 'target', 1, 'then' => [['C', 'Ok']], 'else' => [['C', 'Ng']]], false],
            [['target' => 2], ['C', Valid::UNLESS, 'target', 1, 'then' => [['C', 'Ok']], 'else' => [['C', 'Ng']]], true ],
            // Valid::WITH
            [['target' => 1   ], ['C', Valid::WITH, 'target', 'then' => [['C', 'Ok']], 'else' => [['C', 'Ng']]], true ],
            [['target' => null], ['C', Valid::WITH, 'target', 'then' => [['C', 'Ok']], 'else' => [['C', 'Ng']]], false],
            // Valid::WITHOUT
            [['target' => 1   ], ['C', Valid::WITHOUT, 'target', 'then' => [['C', 'Ok']], 'else' => [['C', 'Ng']]], false],
            [['target' => null], ['C', Valid::WITHOUT, 'target', 'then' => [['C', 'Ok']], 'else' => [['C', 'Ng']]], true ],
            // Valid::IF_NO_ERROR
            [['target' => 1], ['C', Valid::IF_NO_ERROR, 'then' => [['C', 'Ok']], 'else' => [['C', 'Ng']]], true ],
            // [['target' => 2], ['C', Valid::IF_NO_ERROR, 'then' => [['C', 'Ok']], 'else' => [['C', 'Ng']]], false],
            // Valid::IF_AN_ERROR
            // [['target' => 1], ['C', Valid::IF_AN_ERROR, 'then' => [['C', 'Ok']], 'else' => [['C', 'Ng']]], true ],
            [['target' => 2], ['C', Valid::IF_AN_ERROR, 'then' => [['C', 'Ok']], 'else' => [['C', 'Ng']]], false],
            // Valid::SATISFY
            [['target' => 1], ['C', Valid::SATISFY, function (Context $c) { return $c->value == 1 ? true : $c->appendError("@NG");}], true ],
            [['target' => 2], ['C', Valid::SATISFY, function (Context $c) { return $c->value == 1 ? true : $c->appendError("@NG");}], false],
            // Valid::REQUIRED
            [['target' => 1   ], ['C', Valid::REQUIRED], true ],
            [['target' => null], ['C', Valid::REQUIRED], false],
            // Valid::REQUIRED_IF
            [['target' => 1   , 'foo' => 1], ['C', Valid::REQUIRED_IF, 'foo', 1], true ],
            [['target' => null, 'foo' => 1], ['C', Valid::REQUIRED_IF, 'foo', 1], false],
            [['target' => null, 'foo' => 2], ['C', Valid::REQUIRED_IF, 'foo', 1], true ],
            // Valid::REQUIRED_UNLESS
            [['target' => 1   , 'foo' => 2], ['C', Valid::REQUIRED_UNLESS, 'foo', 1], true ],
            [['target' => null, 'foo' => 2], ['C', Valid::REQUIRED_UNLESS, 'foo', 1], false],
            [['target' => null, 'foo' => 1], ['C', Valid::REQUIRED_UNLESS, 'foo', 1], true ],
            // Valid::REQUIRED_WITH
            [['target' => 1   , 'foo' => 1   ], ['C', Valid::REQUIRED_WITH, 'foo'], true ],
            [['target' => null, 'foo' => 1   ], ['C', Valid::REQUIRED_WITH, 'foo'], false],
            [['target' => null, 'foo' => null], ['C', Valid::REQUIRED_WITH, 'foo'], true ],
            // Valid::REQUIRED_WITHOUT
            [['target' => 1   , 'foo' => null], ['C', Valid::REQUIRED_WITHOUT, 'foo'], true ],
            [['target' => null, 'foo' => null], ['C', Valid::REQUIRED_WITHOUT, 'foo'], false],
            [['target' => null, 'foo' => 1   ], ['C', Valid::REQUIRED_WITHOUT, 'foo'], true ],
            // Valid::BLANK_IF
            [['target' => null, 'foo' => 1], ['C', Valid::BLANK_IF, 'foo', 1], true ],
            [['target' => 1   , 'foo' => 1], ['C', Valid::BLANK_IF, 'foo', 1], false],
            [['target' => 1   , 'foo' => 2], ['C', Valid::BLANK_IF, 'foo', 1], true ],
            // Valid::BLANK_UNLESS
            [['target' => null, 'foo' => 2], ['C', Valid::BLANK_UNLESS, 'foo', 1], true ],
            [['target' => 1   , 'foo' => 2], ['C', Valid::BLANK_UNLESS, 'foo', 1], false],
            [['target' => 1   , 'foo' => 1], ['C', Valid::BLANK_UNLESS, 'foo', 1], true ],
            // Valid::BLANK_WITH
            [['target' => null, 'foo' => 1   ], ['C', Valid::BLANK_WITH, 'foo'], true ],
            [['target' => 1   , 'foo' => 1   ], ['C', Valid::BLANK_WITH, 'foo'], false],
            [['target' => 1   , 'foo' => null], ['C', Valid::BLANK_WITH, 'foo'], true ],
            // Valid::BLANK_WITHOUT
            [['target' => null, 'foo' => null], ['C', Valid::BLANK_WITHOUT, 'foo'], true ],
            [['target' => 1   , 'foo' => null], ['C', Valid::BLANK_WITHOUT, 'foo'], false],
            [['target' => 1   , 'foo' => 1   ], ['C', Valid::BLANK_WITHOUT, 'foo'], true ],
            // Valid::SAME_AS
            [['target' => 1], ['C', Valid::SAME_AS, 1], true ],
            [['target' => 1], ['C', Valid::SAME_AS, 2], false],
            // Valid::NOT_SAME_AS
            [['target' => 1], ['C', Valid::NOT_SAME_AS, 1], false],
            [['target' => 1], ['C', Valid::NOT_SAME_AS, 2], true ],
            // Valid::REGEX
            [['target' => '123'], ['C', Valid::REGEX, '/^[0-9]+$/'], true ],
            [['target' => 'abc'], ['C', Valid::REGEX, '/^[0-9]+$/'], false],
            // Valid::NOT_REGEX
            [['target' => '123'], ['C', Valid::NOT_REGEX, '/^[0-9]+$/'], false],
            [['target' => 'abc'], ['C', Valid::NOT_REGEX, '/^[0-9]+$/'], true ],
            // Valid::MAX_LENGTH
            [['target' => '1'  ], ['C', Valid::MAX_LENGTH, 2], true ],
            [['target' => '123'], ['C', Valid::MAX_LENGTH, 2], false],
            // Valid::MIN_LENGTH
            [['target' => '123'], ['C', Valid::MIN_LENGTH, 2], true ],
            [['target' => '1'  ], ['C', Valid::MIN_LENGTH, 2], false],
            // Valid::LENGTH
            [['target' => '12'], ['C', Valid::LENGTH, 2], true ],
            [['target' => '1' ], ['C', Valid::LENGTH, 2], false],
            // Valid::NUMBER
            [['target' => '123'], ['C', Valid::NUMBER], true ],
            [['target' => 'abc'], ['C', Valid::NUMBER], false],
            // Valid::INTEGER
            [['target' => '123'], ['C', Valid::INTEGER], true ],
            [['target' => 'abc'], ['C', Valid::INTEGER], false],
            // Valid::FLOAT
            [['target' => '1.2'], ['C', Valid::FLOAT, 1], true ],
            [['target' => 'abc'], ['C', Valid::FLOAT, 1], false],
            // Valid::NUMBER_LESS_THAN
            [['target' => 1], ['C', Valid::NUMBER_LESS_THAN, 2], true ],
            [['target' => 3], ['C', Valid::NUMBER_LESS_THAN, 2], false],
            // Valid::NUMBER_LESS_THAN_OR_EQUAL
            [['target' => 1], ['C', Valid::NUMBER_LESS_THAN_OR_EQUAL, 2], true ],
            [['target' => 3], ['C', Valid::NUMBER_LESS_THAN_OR_EQUAL, 2], false],
            // Valid::NUMBER_EQUAL
            [['target' => 1], ['C', Valid::NUMBER_EQUAL, 1], true ],
            [['target' => 3], ['C', Valid::NUMBER_EQUAL, 1], false],
            // Valid::NUMBER_GREATER_THAN
            [['target' => 1], ['C', Valid::NUMBER_GREATER_THAN, 2], false],
            [['target' => 3], ['C', Valid::NUMBER_GREATER_THAN, 2], true ],
            // Valid::NUMBER_GREATER_THAN_OR_EQUAL
            [['target' => 1], ['C', Valid::NUMBER_GREATER_THAN_OR_EQUAL, 2], false],
            [['target' => 3], ['C', Valid::NUMBER_GREATER_THAN_OR_EQUAL, 2], true ],
            // Valid::EMAIL
            [['target' => 'a@b.com'], ['C', Valid::EMAIL], true ],
            [['target' => 'abc'    ], ['C', Valid::EMAIL], false],
            // Valid::URL
            [['target' => 'http://github.com/rebet/rebet'], ['C', Valid::URL], true ],
            [['target' => 'abc'                          ], ['C', Valid::URL], false],
            // Valid::IPV4
            [['target' => '192.168.1.1'], ['C', Valid::IPV4], true ],
            [['target' => 'abc'        ], ['C', Valid::IPV4], false],
            // Valid::DIGIT
            [['target' => '123'], ['C', Valid::DIGIT], true ],
            [['target' => 'abc'], ['C', Valid::DIGIT], false],
            // Valid::ALPHA
            [['target' => 'abc'], ['C', Valid::ALPHA], true ],
            [['target' => '123'], ['C', Valid::ALPHA], false],
            // Valid::ALPHA_DIGIT
            [['target' => 'a1'], ['C', Valid::ALPHA_DIGIT], true ],
            [['target' => '--'], ['C', Valid::ALPHA_DIGIT], false],
            // Valid::ALPHA_DIGIT_MARK
            [['target' => 'a-1'], ['C', Valid::ALPHA_DIGIT_MARK], true ],
            [['target' => 'あ' ], ['C', Valid::ALPHA_DIGIT_MARK], false],
            // Valid::HIRAGANA
            [['target' => 'あ'], ['C', Valid::HIRAGANA], true ],
            [['target' => 'a1'], ['C', Valid::HIRAGANA], false],
            // Valid::KANA
            [['target' => 'ア'], ['C', Valid::KANA], true ],
            [['target' => 'a1'], ['C', Valid::KANA], false],
            // Valid::DEPENDENCE_CHAR
            [['target' => 'ア'], ['C', Valid::DEPENDENCE_CHAR], true ],
            [['target' => '♬'], ['C', Valid::DEPENDENCE_CHAR], false],
            // Valid::NG_WORD
            [['target' => 'OK WORD'], ['C', Valid::NG_WORD, ['ng']], true ],
            [['target' => 'NG WORD'], ['C', Valid::NG_WORD, ['ng']], false],
            // Valid::CONTAINS
            [['target' => 1], ['C', Valid::CONTAINS, Gender::values()], true ],
            [['target' => 3], ['C', Valid::CONTAINS, Gender::values()], false],
            // Valid::MIN_COUNT
            [['target' => [1, 2, 3]], ['C', Valid::MIN_COUNT, 2], true],
            [['target' => [1]      ], ['C', Valid::MIN_COUNT, 2], false],
            // Valid::MAX_COUNT
            [['target' => [1]      ], ['C', Valid::MAX_COUNT, 2], true ],
            [['target' => [1, 2, 3]], ['C', Valid::MAX_COUNT, 2], false],
            // Valid::COUNT
            [['target' => [1, 2]], ['C', Valid::COUNT, 2], true ],
            [['target' => [1]   ], ['C', Valid::COUNT, 2], false],
            // Valid::UNIQUE
            [['target' => [1, 2]], ['C', Valid::UNIQUE], true ],
            [['target' => [1, 1]], ['C', Valid::UNIQUE], false],
            // Valid::DATETIME
            [['target' => '2010-01-23'], ['C', Valid::DATETIME], true ],
            [['target' => 'abc'       ], ['C', Valid::DATETIME], false],
            // Valid::FUTURE_THAN
            [['target' => '2100-01-01'], ['C', Valid::FUTURE_THAN, 'now'], true ],
            [['target' => '1900-01-01'], ['C', Valid::FUTURE_THAN, 'now'], false],
            // Valid::FUTURE_THAN_OR_EQUAL
            [['target' => '2100-01-01'], ['C', Valid::FUTURE_THAN_OR_EQUAL, 'now'], true ],
            [['target' => '1900-01-01'], ['C', Valid::FUTURE_THAN_OR_EQUAL, 'now'], false],
            // Valid::PAST_THAN
            [['target' => '2100-01-01'], ['C', Valid::PAST_THAN, 'now'], false],
            [['target' => '1900-01-01'], ['C', Valid::PAST_THAN, 'now'], true ],
            // Valid::PAST_THAN_OR_EQUAL
            [['target' => '2100-01-01'], ['C', Valid::PAST_THAN_OR_EQUAL, 'now'], false],
            [['target' => '1900-01-01'], ['C', Valid::PAST_THAN_OR_EQUAL, 'now'], true ],
            // Valid::MAX_AGE
            [['target' => '1980-01-01'], ['C', Valid::MAX_AGE, 20], false],
            [['target' => '2000-01-01'], ['C', Valid::MAX_AGE, 20], true ],
            // Valid::MIN_AGE
            [['target' => '1980-01-01'], ['C', Valid::MIN_AGE, 20], true ],
            [['target' => '2000-01-01'], ['C', Valid::MIN_AGE, 20], false],
            // Valid::SEQUENTIAL_NUMBER
            [['target' => [['foo' => '1'], ['foo' => '2']]], ['C', Valid::SEQUENTIAL_NUMBER, 'foo'], true ],
            [['target' => [['foo' => '1'], ['foo' => '3']]], ['C', Valid::SEQUENTIAL_NUMBER, 'foo'], false],
            // Valid::ACCEPTED
            [['target' => '1'], ['C', Valid::ACCEPTED], true ],
            [['target' => '' ], ['C', Valid::ACCEPTED], false],
            // Valid::CORRELATED_REQUIRED
            [['target' => '', 'foo' => 1 , 'bar' => ''], ['C', Valid::CORRELATED_REQUIRED, ['foo', 'bar'], 1], true ],
            [['target' => '', 'foo' => '', 'bar' => ''], ['C', Valid::CORRELATED_REQUIRED, ['foo', 'bar'], 1], false],
            // Valid::CORRELATED_UNIQUE
            [['target' => '', 'foo' => 1, 'bar' => 2], ['C', Valid::CORRELATED_UNIQUE, ['foo', 'bar']], true ],
            [['target' => '', 'foo' => 1, 'bar' => 1], ['C', Valid::CORRELATED_UNIQUE, ['foo', 'bar']], false],
            // Valid::FILE_SIZE
            [['target' => $image_72x72_png], ['C', Valid::FILE_SIZE, 500], true ],
            [['target' => $image_72x72_png], ['C', Valid::FILE_SIZE, 300], false],
            // Valid::FILE_NAME_MATCH
            [['target' => $image_72x72_png                                                    ], ['C', Valid::FILE_NAME_MATCH, '/^\d+x\d+\.png$/'], true ],
            [['target' => new UploadedFile(App::path('/resources/image/72x72.png'), 'foo.png')], ['C', Valid::FILE_NAME_MATCH, '/^\d+x\d+\.png$/'], false],
            // Valid::FILE_SUFFIX_MATCH
            [['target' => $image_72x72_png], ['C', Valid::FILE_SUFFIX_MATCH, '/^png$/'  ], true ],
            [['target' => $image_72x72_png], ['C', Valid::FILE_SUFFIX_MATCH, '/^jpe?g$/'], false],
            // Valid::FILE_MIME_TYPE_MATCH
            [['target' => $image_72x72_png], ['C', Valid::FILE_MIME_TYPE_MATCH, '/^image\/.*$/'], true ],
            [['target' => $image_72x72_png], ['C', Valid::FILE_MIME_TYPE_MATCH, '/^text\/.*$/'], false],
            // Valid::FILE_TYPE_IMAGES
            [['target' => $this->createUploadedFileMock('foo.png', 'image/png')], ['C', Valid::FILE_TYPE_IMAGES], true ],
            [['target' => $this->createUploadedFileMock('foo.csv', 'text/csv') ], ['C', Valid::FILE_TYPE_IMAGES], false],
            // Valid::FILE_TYPE_WEB_IMAGES
            [['target' => $this->createUploadedFileMock('foo.png', 'image/png')], ['C', Valid::FILE_TYPE_WEB_IMAGES], true ],
            [['target' => $this->createUploadedFileMock('foo.bmp', 'image/bmp')], ['C', Valid::FILE_TYPE_WEB_IMAGES], false],
            // Valid::FILE_TYPE_CSV
            [['target' => $this->createUploadedFileMock('foo.csv', 'text/csv')], ['C', Valid::FILE_TYPE_CSV], true ],
            [['target' => $this->createUploadedFileMock('foo.xml', 'text/xml')], ['C', Valid::FILE_TYPE_CSV], false],
            // Valid::FILE_TYPE_ZIP
            [['target' => $this->createUploadedFileMock('foo.zip', 'application/zip')], ['C', Valid::FILE_TYPE_ZIP], true ],
            [['target' => $this->createUploadedFileMock('foo.xml', 'application/xml')], ['C', Valid::FILE_TYPE_ZIP], false],
            // Valid::FILE_IMAGE_MAX_WIDTH
            [['target' => $image_72x72_png], ['C', Valid::FILE_IMAGE_MAX_WIDTH, 73], true ],
            [['target' => $image_72x72_png], ['C', Valid::FILE_IMAGE_MAX_WIDTH, 71], false],
            // Valid::FILE_IMAGE_WIDTH
            [['target' => $image_72x72_png], ['C', Valid::FILE_IMAGE_WIDTH, 72], true ],
            [['target' => $image_72x72_png], ['C', Valid::FILE_IMAGE_WIDTH, 71], false],
            // Valid::FILE_IMAGE_MIN_WIDTH
            [['target' => $image_72x72_png], ['C', Valid::FILE_IMAGE_MIN_WIDTH, 71], true ],
            [['target' => $image_72x72_png], ['C', Valid::FILE_IMAGE_MIN_WIDTH, 73], false],
            // Valid::FILE_IMAGE_MAX_HEIGHT
            [['target' => $image_72x72_png], ['C', Valid::FILE_IMAGE_MAX_HEIGHT, 73], true ],
            [['target' => $image_72x72_png], ['C', Valid::FILE_IMAGE_MAX_HEIGHT, 71], false],
            // Valid::FILE_IMAGE_HEIGHT
            [['target' => $image_72x72_png], ['C', Valid::FILE_IMAGE_HEIGHT, 72], true ],
            [['target' => $image_72x72_png], ['C', Valid::FILE_IMAGE_HEIGHT, 71], false],
            // Valid::FILE_IMAGE_MIN_HEIGHT
            [['target' => $image_72x72_png], ['C', Valid::FILE_IMAGE_MIN_HEIGHT, 71], true ],
            [['target' => $image_72x72_png], ['C', Valid::FILE_IMAGE_MIN_HEIGHT, 73], false],
            // Valid::FILE_IMAGE_ASPECT_RATIO
            [['target' => $image_72x72_png], ['C', Valid::FILE_IMAGE_ASPECT_RATIO, 1, 1], true ],
            [['target' => $image_72x72_png], ['C', Valid::FILE_IMAGE_ASPECT_RATIO, 2, 1], false],


        ];
    }

    /**
     * @dataProvider dataValidationInvoke
     */
    public function test_validateInvoke(array $data, array $rule, bool $expect_valid)
    {
        App::setLocale('en');
        $validator    = new Validator($data);
        $valid_data   = $validator->validate('C', ['target' => ['rule' => [$rule]]]);
        // $valid_errors = $validator->errors();
        $this->assertSame($expect_valid, !is_null($valid_data));
    }

    public function test_validate_argsTypeCheck()
    {
        App::setLocale('en');
        $validator  = new Validator(['foo' => 'FOO', 'bar' => 'BAR']);

        $valid_data = $validator->validate('C', [
            'foo' => [
                'rule'  => [
                    ['C', Valid::REQUIRED]
                ]
            ]
        ]);
        $this->assertNotNull($valid_data);
        $this->assertSame('FOO', $valid_data->foo);
        $this->assertSame(null, $valid_data->bar);

        $valid_data = $validator->validate('C', [
            'foo' => [
                'rule'  => [
                    ['C', Valid::REQUIRED]
                ]
            ],
            'bar' => [
                'rule'  => [
                    ['C', Valid::REQUIRED]
                ]
            ]
        ]);
        $this->assertNotNull($valid_data);
        $this->assertSame('FOO', $valid_data->foo);
        $this->assertSame('BAR', $valid_data->bar);

        $valid_data = $validator->validate('C', [
            [
                'foo' => [
                    'rule'  => [
                        ['C', Valid::REQUIRED]
                    ]
                ]
            ],
            [
                'bar' => [
                    'rule'  => [
                        ['C', Valid::REQUIRED]
                    ]
                ]
            ],
        ]);
        $this->assertNotNull($valid_data);
        $this->assertSame('FOO', $valid_data->foo);
        $this->assertSame('BAR', $valid_data->bar);

        $valid_data = $validator->validate('C', FooValidation::class);
        $this->assertNotNull($valid_data);
        $this->assertSame('FOO', $valid_data->foo);
        $this->assertSame(null, $valid_data->bar);

        $valid_data = $validator->validate('C', [FooValidation::class, BarValidation::class]);
        $this->assertNotNull($valid_data);
        $this->assertSame('FOO', $valid_data->foo);
        $this->assertSame('BAR', $valid_data->bar);

        $valid_data = $validator->validate('C', new FooValidation());
        $this->assertNotNull($valid_data);
        $this->assertSame('FOO', $valid_data->foo);
        $this->assertSame(null, $valid_data->bar);

        $valid_data = $validator->validate('C', [new FooValidation(), new BarValidation()]);
        $this->assertNotNull($valid_data);
        $this->assertSame('FOO', $valid_data->foo);
        $this->assertSame('BAR', $valid_data->bar);
    }

    public function test_validate_beforeFilter()
    {
        App::setLocale('en');
        $validator  = new Validator(['foo' => 'foo']);

        $valid_data = $validator->validate('C', [
            'foo' => [
                'before' => function ($value) { return is_string($value) ? strtoupper($value) : $value ; },
                'rule'  => [
                    ['C', Valid::REQUIRED],
                    ['C', Valid::REGEX, '/^[A-Z]+$/'],
                ]
            ]
        ]);
        $this->assertNotNull($valid_data);
        $this->assertSame('FOO', $valid_data->foo);
    }

    public function test_validate_afterFilter()
    {
        App::setLocale('en');
        $validator  = new Validator(['foo' => 'foo']);

        $valid_data = $validator->validate('C', [
            'foo' => [
                'rule'  => [
                    ['C', Valid::REQUIRED],
                    ['C', Valid::ALPHA],
                ],
                'after' => function ($value) { return is_string($value) ? strtoupper($value) : $value ; },
            ]
        ]);
        $this->assertNotNull($valid_data);
        $this->assertSame('FOO', $valid_data->foo);
    }

    public function test_validate_convert()
    {
        App::setLocale('en');
        $validator  = new Validator(['foo' => '2001-01-01']);

        $valid_data = $validator->validate('C', [
            'foo' => [
                'rule'  => [
                    ['C', Valid::REQUIRED],
                    ['C', Valid::DATETIME],
                ],
                'convert' => DateTime::class,
            ]
        ]);
        $this->assertNotNull($valid_data);
        $this->assertInstanceOf(DateTime::class, $valid_data->foo);
        $this->assertSame('2001/01/01', $valid_data->foo->format('Y/m/d'));
    }

    public function test_validate_convertWithValidationError()
    {
        App::setLocale('en');
        $validator  = new Validator(['foo' => 'fooo-01-01']);

        $valid_data = $validator->validate('C', [
            'foo' => [
                'rule'  => [
                    ['C', Valid::REQUIRED],
                    ['C', Valid::DATETIME],
                ],
                'convert' => DateTime::class,
            ]
        ]);
        $this->assertNull($valid_data);
    }

    public function test_validate_convertWithConvertError()
    {
        App::setLocale('en');
        $validator  = new Validator(['foo' => '2001-01-01']);

        $valid_data = $validator->validate('C', [
            'foo' => [
                'rule'  => [
                    ['C', Valid::REQUIRED],
                    ['C', Valid::DATETIME],
                ],
                'convert' => Gender::class,
            ]
        ]);
        $this->assertNull($valid_data);
        $this->assertSame(['foo' => ["The value of Foo could not be converted correctly."]], $validator->errors());
    }

    public function test_validate_quiet()
    {
        App::setLocale('en');
        $validator  = new Validator(['foo' => 'abc']);

        $valid_data = $validator->validate('C', [
            'foo' => [
                'rule'  => [
                    ['C', Valid::NUMBER.'?'],
                ]
            ]
        ]);
        $this->assertNotNull($valid_data);
        $this->assertSame([], $validator->errors());
    }

    public function test_validate_quietThenElse()
    {
        App::setLocale('en');
        $rule = [
            'foo' => [
                'rule'  => [
                    ['C', Valid::NUMBER.'?', 'then' => [
                        ['C', Valid::NUMBER_GREATER_THAN, 100]
                    ], 'else' => [
                        ['C', Valid::REGEX, '/^[A-Z]+$/']
                    ]],
                ]
            ]
        ];

        $validator  = new Validator(['foo' => 'abc']);
        $valid_data = $validator->validate('C', $rule);
        $this->assertNull($valid_data);
        $this->assertSame(['foo' => ["The Foo format is invalid."]], $validator->errors());

        $validator  = new Validator(['foo' => '99']);
        $valid_data = $validator->validate('C', $rule);
        $this->assertNull($valid_data);
        $this->assertSame(['foo' => ["The Foo must be greater than 100."]], $validator->errors());
    }

    public function test_validate_then()
    {
        App::setLocale('en');
        $rule = [
            'foo' => [
                'rule'  => [
                    ['C', 'Number', 'then' => [['C', Valid::NUMBER_GREATER_THAN, 100]], 'else' => [['C', 'Ng']]],
                ]
            ]
        ];

        $validator  = new Validator(['foo' => 'abc']);
        $valid_data = $validator->validate('C', $rule);
        $this->assertNull($valid_data);
        $this->assertSame(['foo' => ["The Foo must be number.", "The Foo is NG."]], $validator->errors());

        $validator  = new Validator(['foo' => '99']);
        $valid_data = $validator->validate('C', $rule);
        $this->assertNull($valid_data);
        $this->assertSame(['foo' => ["The Foo must be greater than 100."]], $validator->errors());
    }

    public function test_validate_exitOnError()
    {
        App::setLocale('en');
        $validator  = new Validator(['foo' => 'abc']);
        $valid_data = $validator->validate('C', [
            'foo' => [
                'rule'  => [
                    ['C', 'Number'],
                    ['C', 'Ng'],
                ]
            ]
        ]);
        $this->assertNull($valid_data);
        $this->assertSame(['foo' => ["The Foo must be number.", "The Foo is NG."]], $validator->errors());

        $valid_data = $validator->validate('C', [
            'foo' => [
                'rule'  => [
                    ['C', 'Number:!'], // with '!' option
                    ['C', 'Ng'],
                ]
            ]
        ]);
        $this->assertNull($valid_data);
        $this->assertSame(['foo' => ["The Foo must be number."]], $validator->errors());
    }

    public function test_validate_duplicatedMessage()
    {
        App::setLocale('en');
        $validator  = new Validator(['foo' => 'abc']);
        $valid_data = $validator->validate('C', [
            'foo' => [
                'rule'  => [
                    ['C', 'Ng', '@Error message 1.'],
                    ['C', 'Ng', '@Error message 2.'],
                    ['C', 'Ng', '@Error message 1.'],
                    ['C', 'Ng', '@Error message 3.'],
                    ['C', 'Ng', '@Error message 3.'],
                ]
            ]
        ]);
        $this->assertNull($valid_data);
        $this->assertSame(['foo' => ["Error message 1.", "Error message 2.", "Error message 3."]], $validator->errors()); // The same message is not duplicated
    }

    public function test_validate_typeConsistencyCheck()
    {
        App::setLocale('en');
        $validator  = new Validator(['foo' => ['abc', 99]]);
        $valid_data = $validator->validate('C', [
            'foo' => [
                'rule'  => [
                    ['C', 'Number'],
                ]
            ]
        ]);
        $this->assertNull($valid_data);
        $this->assertSame(['foo' => ["The 1st Foo (abc) must be number."]], $validator->errors());

        $valid_data = $validator->validate('C', [
            'foo' => [
                'rule'  => [
                    ['C', Valid::NUMBER_GREATER_THAN, 100]
                ]
            ]
        ]);
        $this->assertNull($valid_data);
        $this->assertSame(['foo' => ["The 1st Foo (abc) must be number.", "The 2nd Foo (99) must be greater than 100."]], $validator->errors());

        $valid_data = $validator->validate('C', [
            'foo' => [
                'rule'  => [
                    ['C', 'Number'],
                    ['C', Valid::NUMBER_GREATER_THAN, 100]
                ]
            ]
        ]);
        $this->assertNull($valid_data);
        $this->assertSame(['foo' => ["The 1st Foo (abc) must be number.", "The 2nd Foo (99) must be greater than 100."]], $validator->errors());
    }

    public function test_validate_inlineLabelAndI18n()
    {
        App::setLocale('en');
        $validator  = new Validator(['foo' => null]);
        $valid_data = $validator->validate('C', [
            'foo' => [
                'rule'  => [
                    ['C', Valid::REQUIRED],
                ]
            ]
        ]);
        $this->assertNull($valid_data);
        $this->assertSame(['foo' => ["The Foo field is required."]], $validator->errors());

        $valid_data = $validator->validate('C', [
            'foo' => [
                'label' => 'Custom Foo', // Inline labels are a convenient way to specify labels when internationalization is not required
                'rule'  => [
                    ['C', Valid::REQUIRED],
                ]
            ]
        ]);
        $this->assertNull($valid_data);
        $this->assertSame(['foo' => ["The Custom Foo field is required."]], $validator->errors());

        App::setLocale('ja');
        $valid_data = $validator->validate('C', [
            'foo' => [
                'label' => 'Custom Foo',
                'rule'  => [
                    ['C', Valid::REQUIRED],
                ]
            ]
        ]);
        $this->assertNull($valid_data);
        $this->assertSame(['foo' => ["フーを入力して下さい。"]], $validator->errors()); // 'i18n/{locale}/attribute.php' transration settings take precedence over inline labels
    }

    public function test_validate_nest()
    {
        App::setLocale('en');
        $rule = [
            'bank' => [
                'rule'  => [
                    ['CU', Valid::REQUIRED],
                ],
                'nest' => [
                    'name' => [
                        'rule'  => [
                            ['CU', Valid::REQUIRED],
                            ['CU', Valid::MAX_LENGTH, 20],
                        ],
                    ]
                ],
            ],
        ];

        $validator  = new Validator(['bank' => []]);
        $valid_data = $validator->validate('C', $rule);
        $this->assertNull($valid_data);
        $this->assertSame(['bank' => ["The Bank field is required."], 'bank.name' => ["The Bank Name field is required."]], $validator->errors());

        $validator  = new Validator(['bank' => ['name' => null]]);
        $valid_data = $validator->validate('C', $rule);
        $this->assertNull($valid_data);
        $this->assertSame(['bank.name' => ["The Bank Name field is required."]], $validator->errors());

        $validator  = new Validator(['bank' => ['name' => 'bank name']]);
        $valid_data = $validator->validate('C', $rule);
        $this->assertNotNull($valid_data);
        $this->assertInstanceOf(ValidData::class, $valid_data->bank);
        $this->assertSame('bank name', $valid_data->bank->name);

        $rule = [
            'bank' => [
                'label' => 'BANK',
                'nest'  => [
                    'name' => [
                        'rule'  => [
                            ['CU', Valid::REQUIRED],
                        ],
                    ]
                ],
            ],
        ];
        $validator  = new Validator(['bank' => []]);
        $valid_data = $validator->validate('C', $rule);
        $this->assertNull($valid_data);
        $this->assertSame(['bank.name' => ["The BANK Name field is required."]], $validator->errors());

        $rule = [
            'bank' => [
                'label' => 'BANK',
                'nest'  => [
                    'name'  => [
                        'label' => 'Custom Name',
                        'rule'  => [
                            ['CU', Valid::REQUIRED],
                        ],
                    ]
                ],
            ],
        ];
        $validator  = new Validator(['bank' => []]);
        $valid_data = $validator->validate('C', $rule);
        $this->assertNull($valid_data);
        $this->assertSame(['bank.name' => ["The BANK Custom Name field is required."]], $validator->errors());

        $rule = [
            'bank' => [
                'label' => 'BANK',
                'nest'  => [
                    'name'  => [
                        'label' => 'Custom :parent Name',
                        'rule'  => [
                            ['CU', Valid::REQUIRED],
                        ],
                    ]
                ],
            ],
        ];
        $validator  = new Validator(['bank' => []]);
        $valid_data = $validator->validate('C', $rule);
        $this->assertNull($valid_data);
        $this->assertSame(['bank.name' => ["The Custom BANK Name field is required."]], $validator->errors());


        Validator::setNestedAttributeAutoFormat(false);

        $rule = [
            'bank' => [
                'label' => 'BANK',
                'nest'  => [
                    'name'  => [
                        'label' => 'Custom Name',
                        'rule'  => [
                            ['CU', Valid::REQUIRED],
                        ],
                    ]
                ],
            ],
        ];
        $validator  = new Validator(['bank' => []]);
        $valid_data = $validator->validate('C', $rule);
        $this->assertNull($valid_data);
        $this->assertSame(['bank.name' => ["The Custom Name field is required."]], $validator->errors());

        $rule = [
            'bank' => [
                'label' => 'BANK',
                'nest'  => [
                    'name'  => [
                        'label' => 'Custom :parent Name',
                        'rule'  => [
                            ['CU', Valid::REQUIRED],
                        ],
                    ]
                ],
            ],
        ];
        $validator  = new Validator(['bank' => []]);
        $valid_data = $validator->validate('C', $rule);
        $this->assertNull($valid_data);
        $this->assertSame(['bank.name' => ["The Custom BANK Name field is required."]], $validator->errors());
    }

    public function test_validate_nests()
    {
        App::setLocale('en');
        $rule = [
            'shipping_addresses' => [
                'label' => 'Shipping',
                'rule'  => [
                    ['CU', Valid::REQUIRED],
                    ['CU', Valid::MAX_COUNT.'!', 3],
                ],
                'nests' => [
                    'zip' => [
                        'rule'  => [
                            ['CU', Valid::REQUIRED],
                        ],
                    ],
                    'address' => [
                        'rule'  => [
                            ['CU', Valid::REQUIRED],
                        ],
                    ],
                ]
            ],
        ];

        $validator  = new Validator(['shipping_addresses' => [
            ['zip' => '9990071', 'address' => null],
            ['zip' => null, 'address' => 'Address to home'],
        ]]);
        $valid_data = $validator->validate('C', $rule);
        $this->assertNull($valid_data);
        $this->assertSame([
            'shipping_addresses.0.address' => [
                "The Shipping Address field is required."
            ],
            'shipping_addresses.1.zip' => [
                "The Shipping Zip field is required."
            ],
        ], $validator->errors());
    }

    public function test_validate_crud()
    {
        App::setLocale('en');
        $rule = [
            'foo'     => ['rule'  => [['C' , 'Ng']]],
            'bar'     => ['rule'  => [[ 'U', 'Ng']]],
            'baz'     => ['rule'  => [['CU', 'Ng']]],
            'qux'     => ['rule'  => [['C' , 'Ok:?',
                'then' => [['C', 'Ng', '@C ok then C ng'], ['U', 'Ng', '@C ok then U ng']],
                'else' => [['C', 'Ng', '@C ok else C ng'], ['U', 'Ng', '@C ok else U ng']],
            ]]],
            'quxx'    => ['rule'  => [['U' , 'Ok:?',
                'then' => [['C', 'Ng', '@U ok then C ng'], ['U', 'Ng', '@U ok then U ng']],
                'else' => [['C', 'Ng', '@U ok else C ng'], ['U', 'Ng', '@U ok else U ng']],
            ]]],
            'parent'  => [
                'rule' => [['C', 'Ng']],
                'nest' => [
                    'foo'     => ['rule'  => [['C' , 'Ng']]],
                    'bar'     => ['rule'  => [[ 'U', 'Ng']]],
                    'baz'     => ['rule'  => [['CU', 'Ng']]],
                    'qux'     => ['rule'  => [['C' , 'Ok:?',
                        'then' => [['C', 'Ng', '@C ok then C ng'], ['U', 'Ng', '@C ok then U ng']],
                        'else' => [['C', 'Ng', '@C ok else C ng'], ['U', 'Ng', '@C ok else U ng']],
                    ]]],
                    'children' => [
                        'rule'  => [['U', 'Ng']],
                        'nests' => [
                            'foo' => ['rule'  => [['C' , 'Ng']]],
                            'bar' => ['rule'  => [[ 'U', 'Ng']]],
                            'baz' => ['rule'  => [['CU', 'Ng']]],
                        ]
                    ],
                ],
            ],
            'parents' => [
                'rule'  => [['U', 'Ng']],
                'nests' => [
                    'foo'    => ['rule'  => [['C' , 'Ng']]],
                    'bar'    => ['rule'  => [[ 'U', 'Ng']]],
                    'baz'    => ['rule'  => [['CU', 'Ng']]],
                    'quxx'   => ['rule'  => [[ 'U' , 'Ok:?',
                        'then' => [['C', 'Ng', '@U ok then C ng'], ['U', 'Ng', '@U ok then U ng']],
                        'else' => [['C', 'Ng', '@U ok else C ng'], ['U', 'Ng', '@U ok else U ng']],
                    ]]],
                    'child' => [
                        'nest' => [
                            'foo' => ['rule'  => [['C' , 'Ng']]],
                            'bar' => ['rule'  => [[ 'U', 'Ng']]],
                            'baz' => ['rule'  => [['CU', 'Ng']]],
                        ],
                    ],
                ]
            ],
        ];
        $data = [
            'foo'    => 'foo',
            'bar'    => 'bar',
            'baz'    => 'baz',
            'qux'    => 'qux',
            'quxx'   => 'quxx',
            'parent' => [
                'foo'      => 'foo',
                'bar'      => 'bar',
                'baz'      => 'baz',
                'qux'      => 'qux',
                'children' => [
                    ['foo' => 'foo', 'bar' => 'bar', 'baz' => 'baz'],
                ],
            ],
            'parents' => [
                [
                    'foo'   => 'foo',
                    'bar'   => 'bar',
                    'baz'   => 'baz',
                    'quxx'  => 'quxx',
                    'child' => [
                        'foo' => 'foo',
                        'bar' => 'bar',
                        'baz' => 'baz'
                    ]
                ],
            ],
        ];

        $validator  = new Validator($data);
        $valid_data = $validator->validate('C', $rule);
        $this->assertNull($valid_data);
        $this->assertSame([
            'foo'                   => ["The Foo is NG."],
            'baz'                   => ["The Baz is NG."],
            'qux'                   => ["C ok then C ng"],

            'parent'                => ["The Parent is NG."],
            'parent.foo'            => ["The Parent Foo is NG."],
            'parent.baz'            => ["The Parent Baz is NG."],
            'parent.qux'            => ["C ok then C ng"],
            'parent.children.0.foo' => ["The Parent Children Foo is NG."],
            'parent.children.0.baz' => ["The Parent Children Baz is NG."],

            'parents.0.foo'         => ["The Parents Foo is NG."],
            'parents.0.baz'         => ["The Parents Baz is NG."],
            'parents.0.child.foo'   => ["The Parents Child Foo is NG."],
            'parents.0.child.baz'   => ["The Parents Child Baz is NG."],
        ], $validator->errors());

        $validator  = new Validator($data);
        $valid_data = $validator->validate('U', $rule);
        $this->assertNull($valid_data);
        $this->assertSame([
            'bar'                   => ["The Bar is NG."],
            'baz'                   => ["The Baz is NG."],
            'quxx'                  => ["U ok then U ng"],

            'parent.bar'            => ["The Parent Bar is NG."],
            'parent.baz'            => ["The Parent Baz is NG."],
            'parent.children'       => ["The Parent Children is NG."],
            'parent.children.0.bar' => ["The Parent Children Bar is NG."],
            'parent.children.0.baz' => ["The Parent Children Baz is NG."],

            'parents'               => ["The Parents is NG."],
            'parents.0.bar'         => ["The Parents Bar is NG."],
            'parents.0.baz'         => ["The Parents Baz is NG."],
            'parents.0.quxx'        => ["U ok then U ng"],
            'parents.0.child.bar'   => ["The Parents Child Bar is NG."],
            'parents.0.child.baz'   => ["The Parents Child Baz is NG."],
        ], $validator->errors());
    }

    public function test_validate_acceptUndefined()
    {
        App::setLocale('en');
        $rule = [
            'foo' => [
                'rule'  => [
                    ['C', Valid::REQUIRED],
                ]
            ]
        ];

        $validator  = new Validator(['foo' => 'Foo', 'bar' => 'bar']);
        $valid_data = $validator->validate('C', $rule);
        $this->assertSame(['foo' => 'Foo'], $valid_data->toArray());

        $valid_data = $validator->validate('C', $rule, true);
        $this->assertSame(['foo' => 'Foo', 'bar' => 'bar'], $valid_data->toArray());
    }

    /**
     * @expectedException Rebet\Common\Exception\LogicException
     * @expectedExceptionMessage Invalid rules format. A 'rule/then/else' list item should be array.
     */
    public function test_validate_invalidFormatThen()
    {
        App::setLocale('en');
        $validator  = new Validator(['type' => 1]);
        $valid_data = $validator->validate('C', [
            'foo' => [
                'rule'  => ['C', Valid::IF, 'type', 1, 'then' => 'invalid format'],
            ]
        ]);
    }

    /**
     * @expectedException Rebet\Common\Exception\LogicException
     * @expectedExceptionMessage Invalid rules format. A 'rule/then/else' list item should be array.
     */
    public function test_validate_invalidFormatElse()
    {
        App::setLocale('en');
        $validator  = new Validator(['type' => 1]);
        $valid_data = $validator->validate('C', [
            'foo' => [
                'rule'  => ['C', Valid::IF, 'type', 2, 'else' => 'invalid format'],
            ]
        ]);
    }
}
