<?php
namespace Rebet\Tests\Common\Mock;

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
