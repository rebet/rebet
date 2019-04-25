<?php
namespace Rebet\Tests\Mock;

use Rebet\Common\Popuratable;

class Address
{
    use Popuratable;

    public $user_id;
    public $zip;
    public $prefecture;
    public $address;
}
