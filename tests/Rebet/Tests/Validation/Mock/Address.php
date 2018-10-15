<?php
namespace Rebet\Tests\Validation\Mock;

use Rebet\Validation\Validatable;


class Address {
    use Validatable;

    public $zip;
    public $prefecture;
    public $address;
}
