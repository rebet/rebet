<?php
namespace Rebet\Tests\Mock\Validation;

use Rebet\Tools\Strings;
use Rebet\Tests\Mock\Enum\Gender;
use Rebet\Validation\Context;
use Rebet\Validation\Rule;
use Rebet\Validation\Valid;

class UserValidation extends Rule
{
    // Validation rules
    public function rules() : array
    {
        return [
            'user_id' => [
                'label' => '会員ID',
                'rule'  => [
                    ['RUD', Valid::REQUIRED]
                ]
            ],
            'name' => [
                'label' => '氏名',
                'rule'  => [
                    ['CU', Valid::REQUIRED],
                    ['CU', Valid::MAX_LENGTH, 20],
                    ['CU', Valid::DEPENDENCE_CHAR]
                ]
            ],
            'mail_address' => [
                'label' => 'メールアドレス',
                'rule'  => [
                    ['CU', Valid::REQUIRED],
                    ['CU', Valid::EMAIL],
                    ['CU', Valid::IF_NOT_ERROR, 'then' => [
                        ['CU', 'MailAddressExists'] // Invoke custom validation
                    ]],
                ]
            ],
            'password' => [
                'label' => 'パスワード',
                'rule'  => [
                    ['C' , Valid::REQUIRED],
                    ['CU', Valid::MIN_LENGTH, 8]
                ],
            ],
            'password_confirm' => [
                'label' => 'パスワード(確認)',
                'rule'  => [
                    ['CU', Valid::SATISFY, function (Context $c) { return !Auth::isAdmin(); }, 'then' => [
                        ['C' , Valid::REQUIRED],
                        ['CU', Valid::SAME_AS, ':password']
                    ]],
                ],
            ],
            'avatar' => [
                'label' => 'アバター画像',
                'rule'  => [
                    ['CU', Valid::FILE_SIZE, '2M'],
                    ['CU', Valid::FILE_TYPE_WEB_IMAGES]
                ],
            ],
            'gender' => [
                'label' => '性別',
                'rule'  => [
                    ['C', Valid::REQUIRED],
                    ['C', Valid::CONTAINS, Gender::values()]
                ],
                'convert' => Gender::class
            ],
            'birthday' => [
                'label'  => '生年月日',
                'before' => function ($value) { return mb_convert_kana($value, 'a'); },
                'rule'   => [
                    ['C', Valid::REQUIRED],
                    ['C', Valid::DATETIME],
                    ['C', Valid::MIN_AGE, 18],
                    ['C', Valid::MAX_AGE, 100]
                ],
                'convert' => DateTime::class
            ],
            'bank' => [
                'label' => '銀行',
                'nest'  => [
                    'name' => [
                        'label' => ':parent名',
                        'rule'  => [
                            ['CU', Valid::REQUIRED],
                            ['CU', Valid::MAX_LENGTH, 20],
                        ],
                    ]
                ],
            ],
            'shipping_addresses' => [
                'label' => '送付先',
                'rule'  => [
                    ['CU', Valid::REQUIRED],
                    ['CU', Valid::MAX_COUNT.'!', 5],
                    ['CU', Valid::SEQUENTIAL_NUMBER, 'shipping_no'],
                ],
                'nests' => [
                    'zip' => [
                        'label' => ':parent郵便番号',
                        'rule'  => [
                            ['CU', Valid::REQUIRED],
                        ],
                    ],
                    'prefecture_id' => [
                        'label' => ':parent都道府県',
                        'rule'  => [
                            ['CU', Valid::REQUIRED],
                            ['CU', Valid::CONTAINS, range(1, 47)],
                        ],
                    ],
                    'addess' => [
                        'label' => ':parent住所',
                        'rule'  => [
                            ['CU', Valid::REQUIRED],
                            ['CU', Valid::MAX_LENGTH, 127],
                            ['CU', Valid::DEPENDENCE_CHAR],
                        ],
                    ],
                ]
            ],
        ];
    }

    // Define Custom Validation
    protected function validationMailAddressExists(Context $c) : bool
    {
        if ($c->blank()) {
            return true;
        }
        if (Strings::startsWith($c->value, 'invalid@')) {
            $c->appendError("@{$c->label} is not exists.");
            return false;
        }
        if (Strings::startsWith($c->value, 'custom-errors@')) {
            $c->appendError("errors.MailAddressExists");
            return false;
        }
        return true;
    }
}
