<?php
namespace Rebet\Tests\Mock\Stub;

use Rebet\Common\Getsetable;

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