<?php
namespace Rebet\Tests\Mock;

use Rebet\Application\Bootstrap\LoadConfiguration;
use Rebet\Application\Error\ExceptionHandler;
use Rebet\Application\Http\HttpKernel;

class AppHttpKernel extends HttpKernel
{
    // protected function bootstrappers() : array
    // {
    //     return [
    //         LoadConfiguration::class,
    //     ];
    // }

    public function bootstrap() : void
    {
        parent::bootstrap();
        // Do nothing.
    }

    public function exceptionHandler() : ExceptionHandler
    {
        return new ExceptionHandler();
    }
}
