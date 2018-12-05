<?php
namespace Rebet\Tests\Mock;

class JsonSerializableStub implements \JsonSerializable
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function jsonSerialize()
    {
        return $this->value;
    }
}
