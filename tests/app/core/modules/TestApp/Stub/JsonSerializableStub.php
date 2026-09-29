<?php

declare(strict_types=1);

namespace TestApp\Stub;

use Override;

class JsonSerializableStub implements \JsonSerializable
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    #[Override]
    public function jsonSerialize(): mixed
    {
        return $this->value;
    }
}
