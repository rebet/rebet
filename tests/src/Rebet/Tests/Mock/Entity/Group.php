<?php
namespace Rebet\Tests\Mock\Entity;

use Rebet\Database\Annotation\PhpType;
use Rebet\Database\DataModel\Entity;
use Rebet\Tools\DateTime\DateTime;

class Group extends Entity
{
    public $group_id;

    public $name;

    /**
     * @PhpType(DateTime::class)
     */
    public $created_at;

    /**
     * @PhpType(DateTime::class)
     */
    public $updated_at;
}
