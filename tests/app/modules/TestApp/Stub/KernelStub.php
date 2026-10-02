<?php

declare(strict_types=1);

namespace TestApp\Stub;

use Override;
use Rebet\Application\ExceptionHandler;
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

    #[Override]
    protected function bootstrappers(): array
    {
        return $this->bootstrappers;
    }

    #[Override]
    public function handle($input = null, $output = null)
    {
        return $this->result;
    }

    #[Override]
    public function call(string $action, array $parameters = [], $output = null)
    {
        return $this->result;
    }

    #[Override]
    public function terminate(): void {}

    #[Override]
    public function exceptionHandler(): ExceptionHandler
    {
        return new ExceptionHandler();
    }

    #[Override]
    public function fallback(\Throwable $e): int
    {
        return 1;
    }

    #[Override]
    public function report(\Throwable $e): void {}
}
