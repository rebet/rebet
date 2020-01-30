<?php
namespace Rebet\Tests\Mock\Entity;

use Rebet\Database\Annotation\PhpType;
use Rebet\Database\Annotation\PrimaryKey;
use Rebet\Database\DataModel\Entity;

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

    /**
     * @PhpType(DateTime::class)
     */
    public $created_at;

    /**
     * @PhpType(DateTime::class)
     */
    public $updated_at;

    public function user(bool $for_update = false, bool $eager_load = true) : ?User
    {
        return parent::belongsTo(User::class, [], $for_update, $eager_load);
    }
}
