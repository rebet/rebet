<?php
namespace Rebet\Tests\Mock\Entity;

use Rebet\Database\Annotation\Defaults;
use Rebet\Database\Annotation\PhpType;
use Rebet\Database\Annotation\PrimaryKey;
use Rebet\Database\Annotation\Table;
use Rebet\Database\Annotation\Unmap;
use Rebet\Database\DataModel\Entity;
use Rebet\Tools\DateTime\Date;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tests\Mock\Enum\Gender;

/**
 * @Table("users")
 */
class UserWithAnnot extends Entity
{
    /**
     * @PrimaryKey
     */
    public $user_id;

    /**
     * @Defaults("foo")
     */
    public $name;

    /**
     * @PhpType(Gender::class)
     * @Defaults(2)
     */
    public $gender;

    /**
     * @PhpType(Date::class)
     * @Defaults("20 years ago")
     */
    public $birthday;

    /**
     * @Defaults("foo@bar.local")
     */
    public $email;

    /**
     * @Defaults("user")
     */
    public $role;

    /**
     * @PhpType(DateTime::class)
     * @Defaults("now")
     */
    public $created_at;

    /**
     * @PhpType(DateTime::class)
     */
    public $updated_at;

    /**
     * @Unmap
     */
    public $foo;

    /**
     * @Unmap
     */
    public $bar;

    public function age() : ?int
    {
        return $this->birthday ? Date::valueOf($this->birthday)->age() : null ;
    }

    public function fortune(bool $for_update = false, bool $eager_load = true) : ?Fortune
    {
        return $this->belongsTo(Fortune::class, [], $for_update, $eager_load);
    }

    public function bank() : ?Bank
    {
        return $this->hasOne(Bank::class);
    }

    public function articles(?int $limit = null) : array
    {
        return $this->hasMany(Article::class, [], [], null, $limit);
    }
}
