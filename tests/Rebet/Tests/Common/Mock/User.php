<?php
namespace Rebet\Tests\Common\Mock;

use Rebet\Common\Popuratable;
use Rebet\Common\Annotation\Nest;

class User
{
    use Popuratable;

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
}
