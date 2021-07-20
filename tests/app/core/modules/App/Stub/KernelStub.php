<?php
namespace App\Stub;

use Rebet\Application\Error\ExceptionHandler;
use Rebet\Application\Kernel;
use Rebet\Application\Structure;

class KernelStub extends Kernel
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

    public function handle($input = null, $output = null)
    {
        return $this->result;
    }

    public function call(string $action, array $parameters = [], $output = null)
    {
        return $this->result;
    }

    public function terminate() : void
    {
    }

    public function exceptionHandler() : ExceptionHandler
    {
        return new ExceptionHandler();
    }

    public function fallback(\Throwable $e) : int
    {
        return 1;
    }

    public function report(\Throwable $e) : void
    {
    }
}
