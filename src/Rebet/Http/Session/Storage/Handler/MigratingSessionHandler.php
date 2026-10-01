<?php

declare(strict_types=1);

namespace Rebet\Http\Session\Storage\Handler;

use Symfony\Component\HttpFoundation\Session\Storage\Handler\MigratingSessionHandler as SymfonyMigratingSessionHandler;

/**
 * Migrating Session Handler Class
 *
 * This handler is for migrating sessions from the 1st (current) handler to the 2nd (new)
 * handler without stopping the service.
 * When the sessions have been written to the new handler for a period of time longer than
 * the session lifetime, replace this handler with the new one.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class MigratingSessionHandler extends SymfonyMigratingSessionHandler
{
    // Currently, there is nothing to extends
}
