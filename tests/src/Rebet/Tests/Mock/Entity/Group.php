<?php
namespace Rebet\Tests\Mock\Entity;

use Rebet\Database\DataModel\Entity;
use Rebet\Tools\DateTime\DateTime;

class Group extends Entity
{
    public $group_id;
    public $name;
    public ?DateTime $created_at = null;
    public ?DateTime $updated_at = null;
}
