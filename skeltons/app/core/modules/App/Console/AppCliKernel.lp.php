<?php
declare(strict_types=1);

namespace App\Console;

use Override;
use Rebet\Application\Bootstrap\Bootstrapper;
use Rebet\Application\Console\CliKernel;

/**
 * CLI (Command Line Interface) Kernel Class For {! $code_name !} Application
 *
 * NOTE: If we want to change CLI Kernel, we can do it by override methods of this class.
 */
class AppCliKernel extends CliKernel
{
    /**
     * Get core bootstrappers for our application CLI kernel.
     *
     * @return array<int, Bootstrapper|class-string<Bootstrapper>|array<int|string, mixed>>
     */
    #[Override]
    protected function bootstrappers() : array
    {
        return parent::bootstrappers();
    }

    /**
     * Get our application exception handler for CLI.
     *
     * @return AppCliExceptionHandler
     */
    #[Override]
    public function exceptionHandler() : AppCliExceptionHandler
    {
        return new AppCliExceptionHandler($this->output);
    }
}
