<?php
namespace Rebet\Tests\Mock\Entity;

use Rebet\Database\Annotation\Defaults;
use Rebet\Database\Annotation\PhpType;
use Rebet\Database\Annotation\PrimaryKey;
use Rebet\Database\Annotation\Table;
use Rebet\Database\Annotation\Unmap;
use Rebet\Database\DataModel\Entity;
use Rebet\DateTime\Date;
use Rebet\DateTime\DateTime;
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

    protected static function relations() : array
    {
        return [
            'bank'     => ['has_one', Bank::class],
            'articles' => ['has_many', Article::class],
        ];
    }
}
