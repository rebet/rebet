<?php
namespace Rebet\Tests\Mock;

use Rebet\Database\Annotation\Defaults;
use Rebet\Database\Annotation\PhpType;
use Rebet\Database\Annotation\PrimaryKey;
use Rebet\Database\Annotation\Table;
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
     * @Defaults("now")
     */
    public $updated_at;
}
