<?php

namespace App\Console;

use Rebet\Application\Bootstrap\HandleExceptions;
use Rebet\Application\Bootstrap\LetterpressTagCustomizer;
use Rebet\Application\Bootstrap\LoadApplicationConfiguration;
use Rebet\Application\Bootstrap\LoadEnvironmentVariables;
use Rebet\Application\Bootstrap\PropertiesMaskingConfiguration;
use Rebet\Application\Console\CliKernel;
use Rebet\Application\ExceptionHandler;

/**
 * AppCliKernel For Unit Tests
 */
class AppCliKernel extends CliKernel
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
            [PropertiesMaskingConfiguration::class, 'masks' => ['password', 'password_confirm']],
            LoadApplicationConfiguration::class,
            HandleExceptions::class,
            LetterpressTagCustomizer::class,
        ];
    }

    public function exceptionHandler() : ExceptionHandler
    {
        return new AppCliExceptionHandler($this->output);
    }
}
