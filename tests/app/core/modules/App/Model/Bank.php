<?php
namespace App\Model;

use Rebet\Database\Annotation\PrimaryKey;
use Rebet\Database\DataModel\Entity;
use Rebet\Tools\DateTime\DateTime;

class Bank extends Entity
{
    /**
     * @PrimaryKey
     */
    public $user_id;
    public $name;
    public $branch;
    public $number;
    public $holder;
    public ?DateTime $created_at = null;
    public ?DateTime $updated_at = null;

    public function user(bool $for_update = false, bool $eager_load = true) : ?User
    {
        return parent::belongsTo(User::class, [], $for_update, $eager_load);
    }
}
