<?php

declare(strict_types=1);

namespace TestApp\Stub;

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
