<?php
namespace Rebet\Tests\Mock;

use Rebet\Application\Kernel;
use Rebet\Application\Structure;

class KernelMock extends Kernel
{
    protected $bootstrappers;
    protected $result;

    public function __construct(Structure $structure, string $channel, array $bootstrappers = [], $result = null)
    {
        parent::__construct($structure, $channel);
        $this->bootstrappers = $bootstrappers;
        $this->result        = $result;
    }

    protected function bootstrappers() : array
    {
        return $this->bootstrappers;
    }

    public function handle($input, $output = null)
    {
        return $this->result;
    }

    public function call(string $action, array $parameters = [], $output = null)
    {
        return $this->result;
    }

    public function terminate($input, $result) : void
    {
    }
}
