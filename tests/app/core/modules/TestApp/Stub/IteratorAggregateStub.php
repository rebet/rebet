<?php

declare(strict_types=1);

namespace TestApp\Stub;

use Override;

class IteratorAggregateStub implements \IteratorAggregate
{
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    #[Override]
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->data);
    }
}
