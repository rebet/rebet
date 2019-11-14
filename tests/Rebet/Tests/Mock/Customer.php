<?php
namespace Rebet\Tests\Mock;

use Rebet\Common\Describable;
use Rebet\Common\Populatable;

class Customer
{
    use Populatable, Describable;

    public $name;
    public $birthday;
}
