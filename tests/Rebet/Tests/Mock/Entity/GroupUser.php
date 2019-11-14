<?php
namespace Rebet\Tests\Mock\Entity;

use Rebet\Database\Annotation\Defaults;
use Rebet\Database\Annotation\PhpType;
use Rebet\Database\Annotation\PrimaryKey;
use Rebet\Database\DataModel\Entity;
use Rebet\DateTime\Date;
use Rebet\DateTime\DateTime;
use Rebet\Tests\Mock\Enum\GroupPosition;

class GroupUser extends Entity
{
    /**
     * @PrimaryKey
     */
    public $group_id;

    /**
     * @PrimaryKey
     */
    public $user_id;

    /**
     * @PhpType(GroupPosition::class)
     * @Defaults(3)
     */
    public $position;

    /**
     * @PhpType(Date::class)
     * @Defaults("today")
     */
    public $join_on;

    /**
     * @PhpType(DateTime::class)
     * @Defaults("now")
     */
    public $created_at;

    /**
     * @PhpType(DateTime::class)
     */
    public $updated_at;
}
