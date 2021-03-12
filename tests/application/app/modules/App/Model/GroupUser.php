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
    public ?GroupPosition $position = null;

    /**
     * @Defaults("today")
     */
    public ?Date $join_on = null;

    /**
     * @Defaults("now")
     */
    public ?DateTime $created_at = null;
    public ?DateTime $updated_at = null;
}
