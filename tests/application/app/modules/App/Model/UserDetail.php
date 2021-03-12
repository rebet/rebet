<?php
namespace App\Model;

use App\Enum\Gender;
use Rebet\Database\Annotation\PrimaryKey;
use Rebet\Database\Database;
use Rebet\Database\DataModel\Presentation;
use Rebet\Database\Query;
use Rebet\Database\Ransack\Ransack;
use Rebet\Tools\DateTime\Date;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\Utility\Utils;

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

    public function articles($conditions = [], bool $for_update = false) : array
    {
        return $this->hasMany(Article::class, [], $conditions, null, null, $for_update);
    }

    protected static function ransack(Ransack $ransack) : ?Query
    {
        if (Utils::isBlank($ransack->value())) {
            return null;
        }

        switch ($ransack->origin()) {
            case 'has_bank': return $ransack->driver()->sql("B.bank_id IS NOT NULL");
        }

        return parent::ransack($ransack);
    }

    protected static function buildSelectAllSql(Database $db) : Query
    {
        return $db->sql(<<<EOS
            SELECT
                U.*,
                (SELECT COUNT(*) FROM articles AS A WHERE A.user_id = U.user_id) AS article_count
            FROM
                users AS U
                LEFT OUTER JOIN bank AS B
EOS
        );
    }
}
