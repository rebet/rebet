<?php
namespace Rebet\Tests\Validation\Mock;

use Rebet\Validation\Validatable;


class Bank {
    use Validatable;

    public $name;
    public $branch;
    public $number;
    public $holder;
}
