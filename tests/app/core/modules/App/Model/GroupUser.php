<?php
namespace App\Model;

use App\Enum\GroupPosition;
use Rebet\Database\Annotation\Defaults;
use Rebet\Database\Annotation\PrimaryKey;
use Rebet\Database\DataModel\Entity;
use Rebet\Tools\DateTime\Date;
use Rebet\Tools\DateTime\DateTime;

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
     * @Defaults(3)
     */
    public GroupPosition|null $position = null;

    /**
     * @Defaults("today")
     */
    public Date|null $join_on = null;

    /**
     * @Defaults("now")
     */
    public DateTime|null $created_at = null;
    public DateTime|null $updated_at = null;
}
