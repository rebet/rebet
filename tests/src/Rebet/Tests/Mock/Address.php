<?php
namespace Rebet\Tests\Mock;

use Rebet\Tools\Populatable;

class Address
{
    use Populatable;

    public $user_id;
    public $zip;
    public $prefecture;
    public $address;
}
