<?php
declare(strict_types=1);

namespace App\Http;

use Override;
use Rebet\Application\Bootstrap\Bootstrapper;
use Rebet\Application\Http\WebKernel;

/**
 * Web Kernel Class For {! $code_name !} Application
 *
 * NOTE: If we want to change WEB Kernel, we can do it by override methods of this class.
 */
class AppWebKernel extends WebKernel
{
    /**
     * Get core bootstrappers for our application WEB kernel.
     *
     * @return array<int, Bootstrapper|class-string<Bootstrapper>|array<int|string, mixed>>
     */
    #[Override]
    protected function bootstrappers() : array
    {
        return parent::bootstrappers();
    }

    /**
     * Get our application exception handler for WEB.
     *
     * @return AppWebExceptionHandler
     */
    #[Override]
    public function exceptionHandler() : AppWebExceptionHandler
    {
        return new AppWebExceptionHandler();
    }
}
