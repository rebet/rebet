<?php

namespace App\Console;

use Rebet\Application\Console\CliExceptionHandler;

/**
 * AppExceptionHandler For Unit Tests
 */
class AppCliExceptionHandler extends CliExceptionHandler
{
    public function handle($input, \Throwable $e)
    {
        throw $e;
    }
}
