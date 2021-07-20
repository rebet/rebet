<?php
namespace App\Stub;

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
