<?php
namespace Rebet\Tests\Mock;

use Rebet\Common\Annotation\Nest;
use Rebet\Database\Annotation\Unmap;
use Rebet\Database\Entity;

class User extends Entity
{
    public $user_id;
    public $name;
    public $gender;
    public $birthday;
    public $email;
    public $role;
    public $created_at;
    public $updated_at;

    /**
     * @Nest(Bank::class)
     * @Unmap
     */
    public $bank = null;

    /**
     * @Nest(Address::class)
     * @Unmap
     */
    public $shipping_addresses = [];

    public function age() : ?int
    {
        return $this->birthday ? $this->birthday->age() : null ;
    }
}