<?php
namespace App\Stub;

use Rebet\Tools\Support\Getsetable;

class GetsetableStub
{
    use Getsetable;

    /**
     * @var mixed
     */
    private $value;

    public function value($value = null)
    {
        return $this->getset('value', $value);
    }
}
