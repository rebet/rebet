<?php
declare(strict_types=1);

namespace Rebet\Application;

/**
 * Null Structure Class
 *
 * An application structure that always points to a directory guaranteed never to exist
 * (`/dev/null/rebet` on POSIX, `NUL\rebet` on Windows), so any config/env/resource loading
 * based on this structure always resolves to library defaults, regardless of the current
 * working directory or any previously initialized project left there.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class NullStructure extends Structure
{
    /**
     * Create a null (never existing) application structure.
     */
    public function __construct()
    {
        parent::__construct(PHP_OS_FAMILY === 'Windows' ? 'NUL\\rebet' : '/dev/null/rebet');
    }
}
