<?php

declare(strict_types=1);

namespace TestApp\Http;

use Override;
use Rebet\Application\Http\WebExceptionHandler;

/**
 * AppExceptionHandler For Unit Tests
 */
class AppWebExceptionHandler extends WebExceptionHandler
{
    #[Override]
    public function handle($input, \Throwable $e): void
    {
        throw $e;
    }
}
