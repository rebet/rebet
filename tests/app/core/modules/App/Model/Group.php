<?php
namespace App\Model;

use Rebet\Database\DataModel\Entity;
use Rebet\Tools\DateTime\DateTime;

class Group extends Entity
{
    public $group_id;
    public $name;
    public DateTime|null $created_at = null;
    public DateTime|null $updated_at = null;
}
