<?php
namespace Rebet\Tests\Mock\Entity;

use Rebet\Common\Strings;
use Rebet\Common\Utils;
use Rebet\Database\Annotation\PhpType;
use Rebet\Database\Annotation\PrimaryKey;
use Rebet\Database\DataModel\Presentation;
use Rebet\Database\ResultSet;
use Rebet\DateTime\Date;
use Rebet\DateTime\DateTime;
use Rebet\Tests\Mock\Enum\Gender;

class User extends Presentation
{
    /**
     * @PrimaryKey
     */
    public $user_id;

    public $name;

    /**
     * @PhpType(Gender::class)
     */
    public $gender;

    /**
     * @PhpType(Date::class)
     */
    public $birthday;

    public $email;

    public $role;

    /**
     * @PhpType(DateTime::class)
     */
    public $created_at;

    /**
     * @PhpType(DateTime::class)
     */
    public $updated_at;

    public $article_count;

    public function age() : ?int
    {
        return $this->birthday ? Date::valueOf($this->birthday)->age() : null ;
    }

    public function user(bool $for_update = false) : User
    {
        return $this->hasOne(User::class, [], $for_update);
    }

    public function bank(bool $for_update = false) : ?Bank
    {
        return $this->hasOne(Bank::class, [], $for_update);
    }

    public function articles($conditions = [], bool $for_update = false) : ResultSet
    {
        return $this->hasMany(Article::class, [], $conditions, null, null, $for_update);
    }

//     protected static function buildSelectSql(array $conditions = []) : array
//     {
//         return <<<EOS
//             SELECT
//                 U.*,
//                 (SELECT COUNT(*) FROM articles AS A WHERE A.user_id = U.user_id) AS article_count
//             FROM
//                 users AS U
//                 LEFT OUTER JOIN bank AS B
    // EOS
//         ;
//     }

    protected static function buildConditionalExpression($key, $value, ?string $table_alias = null) : array
    {
        if (Utils::isBlank($value)) {
            return [null, $value];
        }

        switch ($key) {
            case 'has_bank': return ["B.bank_id IS NOT NULL", $value];
        }

        return parent::buildConditionalExpression($key, $value, Strings::startsWith($key, 'bank_') ? 'B' : 'U');
    }
}
