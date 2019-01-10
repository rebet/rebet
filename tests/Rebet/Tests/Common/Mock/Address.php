<?php
namespace Rebet\Tests\Common\Mock;

use Rebet\Common\Popuratable;

class Address
{
    use Popuratable;

    public $user_id;
    public $zip;
    public $prefecture;
    public $address;
}
