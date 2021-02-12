<?php
namespace Rebet\Tests\Mock\Entity;

use Rebet\Tools\Utility\Strings;
use Rebet\Tools\Utility\Utils;
use Rebet\Database\Annotation\PrimaryKey;
use Rebet\Database\Database;
use Rebet\Database\DataModel\Presentation;
use Rebet\Database\ResultSet;
use Rebet\Tools\DateTime\Date;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tests\Mock\Enum\Gender;

class UserDetail extends Presentation
{
    /**
     * @PrimaryKey
     */
    public $user_id;
    public $name;
    public Gender $gender;
    public Date $birthday;
    public $email;
    public $role;
    public DateTime $created_at;
    public ?DateTime $updated_at;
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

    protected static function buildSelectAllSql(Database $db) : string
    {
        return <<<EOS
            SELECT
                U.*,
                (SELECT COUNT(*) FROM articles AS A WHERE A.user_id = U.user_id) AS article_count
            FROM
                users AS U
                LEFT OUTER JOIN bank AS B
EOS
        ;
    }
}
