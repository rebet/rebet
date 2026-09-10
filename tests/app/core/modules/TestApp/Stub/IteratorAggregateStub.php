<?php
namespace TestApp\Stub;

class IteratorAggregateStub implements \IteratorAggregate
{
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getIterator() : \Traversable
    {
        return new \ArrayIterator($this->data);
    }
}
