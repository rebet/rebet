<?php

declare(strict_types=1);

namespace TestApp\Console;

use Rebet\Application\Console\CliExceptionHandler;

/**
 * AppExceptionHandler For Unit Tests
 */
class AppCliExceptionHandler extends CliExceptionHandler
{
    public function handle($input, \Throwable $e): void
    {
        throw $e;
    }
}
