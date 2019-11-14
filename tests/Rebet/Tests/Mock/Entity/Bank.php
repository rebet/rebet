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

    protected static function relations() : array
    {
        return [
            'user' => ['belongs_to', User::class],
        ];
    }
}
