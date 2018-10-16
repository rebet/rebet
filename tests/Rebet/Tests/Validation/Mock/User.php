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
    public $sex;
    
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
                ['_RUD', 'AUG', Valid::REQUIRED.'!']
            ],
            'name' => [
                ['C_U_', 'AUG', Valid::REQUIRED.'!'],
                ['C_U_', 'AUG', Valid::MAX_LENGTH, 20],
                ['C_U_', 'AUG', Valid::DEPENDENCE_CHAR]
            ],
            'mail_address' => [
                ['C_U_', 'AUG', Valid::REQUIRED.'!'],
                ['C_U_', 'AUG', Valid::MAIL_ADDRESS],
                ['C_U_', 'AUG', Valid::STIL_NO_ERROR, 'then' =>[
                    ['C_U_', 'AUG', 'mail_address_exists'] // カスタム Validation の実行
                ]],
            ],
            'password' => [
                ['C_U_', 'AUG', Valid::REQUIRED.'!'],
                ['C_U_', 'AUG', Valid::MIN_LENGTH, 8]
            ],
            'password_confirm' => [
                ['C_U_', '_UG', Valid::REQUIRED.'!'],
                ['C_U_', '_UG', Valid::SAME_AS_INPUTTED, 'password']
            ],
            'avatar' => [
                ['C_U_', 'AUG', Valid::FILE_SIZE, '2M'],
                ['C_U_', 'AUG', Valid::FILE_WEB_IMAGE_SUFFIX]
            ],
            'sex' => [
                ['C_U_', 'AUG', Valid::REQUIRED.'!'],
                ['C_U_', 'AUG', Valid::CONTAINS, Gender::values()]
            ],
            'birthday' => [
                ['C_U_', 'AUG', Valid::REQUIRED.'!'],
                ['C_U_', 'AUG', Valid::DATETIME.'!', 'convert' => DateTime::class],
                ['C_U_', 'AUG', Valid::AGE_GREATER_EQUAL, 18],
                ['C_U_', 'AUG', Valid::AGE_LESS_EQUAL, 100]
            ],
            'shipping_addresses' => [
                ['C_U_', 'AUG', Valid::REQUIRED.'!'],
                ['C_U_', 'AUG', Valid::MAX_SELECT_COUNT.'!', 5],
                ['C_U_', 'AUG', Valid::SUB_FORM_SERIAL_NO, 'shipping_no'],
            ],
        ];
    }
    
    // カスタム Validation の定義
    protected function valid_mail_address_exists($field, $label, $value)
    {
        if ($this->_empty($value)) {
            return null;
        }
        if (Dao::exists(
            "SELECTFROM user WHERE mail_address=:mail_address" . (!empty($this->user_id) ? " AND user_id<>:user_id" : ""),
            ['mail_address' => $value, 'user_id' => $this->user_id]
        )) {
            return "ご指定の{$label}は既に存在しています。";
        }
        return null;
    }
}
