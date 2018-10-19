<?php
namespace Rebet\Tests\Validation\Mock;

use Rebet\Validation\Rule;
use Rebet\Validation\Type;

class User implements Rule
{
    // Validation ルール定義
    // 仕様策定中
    protected function rules()
    {
        return [
            'user_id' => [
                'label' => '会員ID',
                'rule' => [
                    ['RUD', Valid::REQUIRED.'!']
                ]
            ],
            'name' => [
                'label' => '氏名',
                'rule' => [
                    ['CU', Valid::REQUIRED.'!'],
                    ['CU', Valid::MAX_LENGTH, 20],
                    ['CU', Valid::DEPENDENCE_CHAR]
                ]
            ],
            'mail_address' => [
                'label' => 'メールアドレス',
                'rule' => [
                    ['CU', Valid::REQUIRED.'!'],
                    ['CU', Valid::MAIL_ADDRESS],
                    ['CU', Valid::IF_STIL_NO_ERROR, 'then' => [
                        ['CU', 'mail_address_exists'] // カスタム Validation の実行
                    ]],
                ]
            ],
            'password' => [
                'label' => 'パスワード',
                'rule' => [
                    ['C' , Valid::REQUIRED.'!'],
                    ['CU', Valid::MIN_LENGTH, 8]
                ],
            ],
            'password_confirm' => [
                'label' => 'パスワード(確認)',
                'rule' => [
                    ['CU', Valid::IF, function ($context) {
                        return !Auth::isAdmin();
                    }, 'then' => [
                        ['C' , Valid::REQUIRED.'!'],
                        ['CU', Valid::SAME_AS_INPUTTED, 'password']
                    ]],
                ],
            ],
            'avatar' => [
                'label' => 'アバター画像',
                'rule' => [
                    ['CU', Valid::FILE_SIZE, '2M'],
                    ['CU', Valid::FILE_WEB_IMAGE_SUFFIX]
                ],
            ],
            'gender' => [
                'label' => '性別',
                'rule' => [
                    ['C', Valid::REQUIRED.'!'],
                    ['C', Valid::CONTAINS, Gender::values()]
                ],
                'convert' => Gender::class
            ],
            'birthday' => [
                'label' => '生年月日',
                'before' => function ($value) {
                    return mb_convert_kana($value, 'a');
                },
                'rule' => [
                    ['C', Valid::REQUIRED.'!'],
                    ['C', Valid::DATETIME.'!'],
                    ['C', Valid::AGE_GREATER_EQUAL, 18],
                    ['C', Valid::AGE_LESS_EQUAL, 100]
                ],
                'convert' => DateTime::class
            ],
            'shipping_addresses' => [
                'label' => '送付先',
                'rule' => [
                    ['CU', Valid::REQUIRED.'!'],
                    ['CU', Valid::MAX_SELECT_COUNT.'!', 5],
                    ['CU', Valid::SUB_FORM_SERIAL_NO, 'shipping_no'],
                ],
                'nests' => [
                    'zip' => [
                        'label' => ':parent郵便番号',
                        'rule' => [
                            ['CU', Valid::REQUIRED.'!'],
                        ],
                    ],
                ]
            ],
        ];
    }
    
    // カスタム Validation の定義
    protected function valid_mail_address_exists($field, $label, $value)
    {
        return null;
    }
}
