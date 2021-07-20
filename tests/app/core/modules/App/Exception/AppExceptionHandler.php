<?php

namespace App\Exception;

use Rebet\Application\Error\ExceptionHandler;

/**
 * AppExceptionHandler For Unit Tests
 */
class AppExceptionHandler extends ExceptionHandler 
{
    public function handle($input, $output, \Throwable $e)
    {
        throw $e;
    }
}