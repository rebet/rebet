<?php

declare(strict_types=1);

namespace TestApp\Stub;

use Override;

class ToStringStub
{
    /**
     * @var string
     */
    private $string;

    public function __construct($string)
    {
        $this->string = $string;
    }

    #[Override]
    public function __toString()
    {
        return $this->string;
    }
}
