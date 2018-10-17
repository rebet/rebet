<?php
namespace Rebet\Tests\Common\Mock;

use Rebet\Common\Popuratable;

class Bank
{
    use Popuratable;

    public $name;
    public $branch;
    public $number;
    public $holder;
}
