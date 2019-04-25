<?php
namespace Rebet\Tests\Mock;

use Rebet\Common\Annotation\Nest;
use Rebet\Common\Popuratable;

class User
{
    use Popuratable;

    public $user_id;
    public $email;
    public $role;
    public $name;
    public $birthday;

    /**
     * @Nest(Bank::class)
     */
    public $bank = null;

    /**
     * @Nest(Address::class)
     */
    public $shipping_addresses = [];

    public function age() : ?int
    {
        return $this->birthday ? $this->birthday->age() : null ;
    }
}
