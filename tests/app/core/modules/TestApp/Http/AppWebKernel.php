<?php

declare(strict_types=1);

namespace TestApp\Http;

use Override;
use Rebet\Application\Bootstrap\EmailValidatorEnable;
use Rebet\Application\Bootstrap\HandleExceptions;
use Rebet\Application\Bootstrap\LetterpressTagCustomizer;
use Rebet\Application\Bootstrap\LoadApplicationConfiguration;
use Rebet\Application\Bootstrap\LoadEnvironmentVariables;
use Rebet\Application\Bootstrap\LoadRoutingConfiguration;
use Rebet\Application\Bootstrap\PropertiesMaskingConfiguration;
use Rebet\Application\Http\WebKernel;

/**
 * AppWebKernel For Unit Tests
 */
class AppWebKernel extends WebKernel
{
    #[Override]
    public function bootstrap(): void
    {
        parent::bootstrap();
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    protected function bootstrappers(): array
    {
        return [
            LoadEnvironmentVariables::class,
            [PropertiesMaskingConfiguration::class, 'masks' => ['password', 'password_confirm']],
            LoadApplicationConfiguration::class,
            LoadRoutingConfiguration::class,
            HandleExceptions::class,
            LetterpressTagCustomizer::class,
            EmailValidatorEnable::class,
        ];
    }

    #[Override]
    public function exceptionHandler(): AppWebExceptionHandler
    {
        return new AppWebExceptionHandler();
    }
}
