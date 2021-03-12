<?php

namespace App\Http;

use App\Exception\AppExceptionHandler;
use Rebet\Application\Bootstrap\HandleExceptions;
use Rebet\Application\Bootstrap\LetterpressTagCustomizer;
use Rebet\Application\Bootstrap\LoadApplicationConfiguration;
use Rebet\Application\Bootstrap\LoadEnvironmentVariables;
use Rebet\Application\Bootstrap\LoadFrameworkConfiguration;
use Rebet\Application\Bootstrap\LoadRoutingConfiguration;
use Rebet\Application\Bootstrap\PropertiesMaskingConfiguration;
use Rebet\Application\Error\ExceptionHandler;
use Rebet\Application\Http\HttpKernel;

/**
 * AppHttpKernel For Unit Tests
 */
class AppHttpKernel extends HttpKernel 
{
    public function bootstrap() : void
    {
        parent::bootstrap();
    }
	
    /**
     * {@inheritDoc}
     */
    protected function bootstrappers() : array
    {
        return [
            LoadEnvironmentVariables::class,
            LoadFrameworkConfiguration::class,
            [PropertiesMaskingConfiguration::class, 'masks' => ['password', 'password_confirm']],
            LoadApplicationConfiguration::class,
            LoadRoutingConfiguration::class,
            HandleExceptions::class,
            LetterpressTagCustomizer::class,
        ];
    }
    
    public function exceptionHandler() : ExceptionHandler
    {
        return new AppExceptionHandler();
    }
}