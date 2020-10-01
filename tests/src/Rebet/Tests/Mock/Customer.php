<?php
namespace Rebet\Tests\Mock;

use Rebet\Tools\Describable;
use Rebet\Tools\Populatable;

class Customer
{
    use Populatable, Describable;

    public $name;
    public $birthday;
}
