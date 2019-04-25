<?php
namespace Rebet\Tests\Mock;

use Rebet\Common\Popuratable;

class Bank
{
    use Popuratable;

    public $user_id;
    public $name;
    public $branch;
    public $number;
    public $holder;
}
