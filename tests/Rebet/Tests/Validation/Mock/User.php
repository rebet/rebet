<?php
namespace Rebet\Tests\Validation\Mock;

use Rebet\Validation\Validatable;
use Rebet\Validation\Annotation\Nest;
use Rebet\Validation\Annotation\Label;
use Rebet\Validation\Annotation\File;

class User
{
    use Validatable;

    /**
     * @Label("会員ID")
     */
    public $user_id;
    
    /**
     * @Label("氏名")
     */
    public $name;
    
    /**
     * @Label("メールアドレス")
     */
    public $mail_address;
    
    /**
     * @Label("パスワード")
     */
    public $password;
    
    /**
     * @Label("パスワード(確認)")
     */
    public $password_confirm;
    
    /**
     * @Label("性別")
     */
    public $gender;
    
    /**
     * @Label("生年月日")
     */
    public $birthday;
    
    /**
     * @File
     * @Label("アバター画像")
     */
    public $avatar;
    
    /**
     * @Nest(Bank::class)
     * @Label("口座情報")
     */
    public $bank = null;
    
    /**
     * @Nest(Address::class)
     * @Label("配送先")
     */
    public $shipping_addresses = [];
    
    // Validation ルール定義
    // 仕様策定中
    protected function rules()
    {
        return [
            'user_id' => [
                ['RUD', Valid::REQUIRED.'!']
            ],
            'name' => [
                ['CU', Valid::REQUIRED.'!'],
                ['CU', Valid::MAX_LENGTH, 20],
                ['CU', Valid::DEPENDENCE_CHAR]
            ],
            'mail_address' => [
                ['CU', Valid::REQUIRED.'!'],
                ['CU', Valid::MAIL_ADDRESS],
                ['CU', Valid::IF_STIL_NO_ERROR, 'then' => [
                    ['CU', 'mail_address_exists'] // カスタム Validation の実行
                ]],
            ],
            'password' => [
                ['C' , Valid::REQUIRED.'!'],
                ['CU', Valid::MIN_LENGTH, 8]
            ],
            'password_confirm' => [
                ['CU', Valid::IF_LOGIN_ROLE, Role::ADMIN(), 'else' => [
                    ['C' , Valid::REQUIRED . '!'],
                    ['CU', Valid::SAME_AS_INPUTTED, 'password']
                ]],
            ],
            'avatar' => [
                ['CU', Valid::FILE_SIZE, '2M'],
                ['CU', Valid::FILE_WEB_IMAGE_SUFFIX]
            ],
            'gender' => [
                ['C', Valid::REQUIRED.'!'],
                ['C', Valid::CONTAINS, Gender::values()]
            ],
            'birthday' => [
                ['C', Valid::REQUIRED.'!'],
                ['C', Valid::DATETIME.'!', 'convert' => DateTime::class], // @Convert アノテーションの方がいいか？
                ['C', Valid::AGE_GREATER_EQUAL, 18],
                ['C', Valid::AGE_LESS_EQUAL, 100]
            ],
            'shipping_addresses' => [
                ['CU', Valid::REQUIRED.'!'],
                ['CU', Valid::MAX_SELECT_COUNT.'!', 5],
                ['CU', Valid::SUB_FORM_SERIAL_NO, 'shipping_no'],
            ],
        ];
    }
    
    // カスタム Validation の定義
    protected function valid_mail_address_exists($field, $label, $value)
    {
        return null;
    }
}
