<?php
namespace Rebet\Tests\Validation;

use Rebet\Config\Config;
use Rebet\DateTime\DateTime;
use Rebet\Foundation\App;
use Rebet\Tests\Mock\Gender;
use Rebet\Tests\RebetTestCase;
use Rebet\Validation\BuiltinValidations;
use Rebet\Validation\Context;
use Rebet\Validation\Valid;
use Rebet\Validation\Validator;

class ValidatorTest extends RebetTestCase
{
    private $root;

    public function setup()
    {
        parent::setUp();
        DateTime::setTestNow('2010-01-23 12:34:56');

        Config::application([
            BuiltinValidations::class => [
                'resources_dir' => ['vfs://root/resources'],
                'customs'       => [
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
     * @dataProvider dataValidationInvoke
     */
    public function test_validateInvoke(array $data, array $rule, bool $expect_valid)
    {
        App::setLocale('en');
        $validator    = new Validator($data);
        $valid_data   = $validator->validate('C', ['target' => ['rule' => [$rule]]]);
        $valid_errors = $validator->errors();
        $this->assertSame($expect_valid, !is_null($valid_data));
    }

    public function dataValidationInvoke() : array
    {
        $this->setUp();
        return [
            // Valid::IF
            [['target' => 1], ['C', Valid::IF, 'target', 1, 'then' => [['C', 'Ok']], 'else' => [['C', 'Ng']]], true ],
            [['target' => 2], ['C', Valid::IF, 'target', 1, 'then' => [['C', 'Ok']], 'else' => [['C', 'Ng']]], false],
            // Valid::UNLESS
            [['target' => 1], ['C', Valid::UNLESS, 'target', 1, 'then' => [['C', 'Ok']], 'else' => [['C', 'Ng']]], false],
            [['target' => 2], ['C', Valid::UNLESS, 'target', 1, 'then' => [['C', 'Ok']], 'else' => [['C', 'Ng']]], true ],
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
            // Valid::MAX_NUMBER
            [['target' => 1], ['C', Valid::MAX_NUMBER, 2], true ],
            [['target' => 3], ['C', Valid::MAX_NUMBER, 2], false],
            // Valid::MIN_NUMBER
            [['target' => 1], ['C', Valid::MIN_NUMBER, 2], false],
            [['target' => 3], ['C', Valid::MIN_NUMBER, 2], true ],
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
        ];
    }
}
