<?php

namespace App\Http;

use Rebet\Application\Http\WebExceptionHandler;

/**
 * AppExceptionHandler For Unit Tests
 */
class AppWebExceptionHandler extends WebExceptionHandler
{
    public function handle($input, \Throwable $e)
    {
        throw $e;
    }
}
