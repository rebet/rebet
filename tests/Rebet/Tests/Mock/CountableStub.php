<?php
namespace Rebet\Tests\Mock;

class CountableStub implements \Countable
{
    private $count;

    public function __construct($count)
    {
        $this->count = $count;
    }

    public function count()
    {
        return $this->count;
    }
}
