<?php
namespace App\Stub;

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

    public function __toString()
    {
        return $this->string;
    }
}
