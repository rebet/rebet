<?php

declare(strict_types=1);

namespace TestApp\Stub;

use Override;

class CountableStub implements \Countable
{
    private $count;

    public function __construct($count)
    {
        $this->count = $count;
    }

    #[Override]
    public function count(): int
    {
        return $this->count;
    }
}
