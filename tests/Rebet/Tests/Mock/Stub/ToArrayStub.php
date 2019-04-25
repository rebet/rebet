<?php
namespace Rebet\Tests\Mock\Stub;

class ToArrayStub
{
    private $array;

    public function __construct(array $array)
    {
        $this->array = $array;
    }

    public function toArray() : array
    {
        return $this->array;
    }
}
