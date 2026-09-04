<?php
declare(strict_types=1);

namespace Rebet\Application\Bootstrap;

use Rebet\Application\Kernel;
use Rebet\Mail\Validator\EmailValidator;

/**
 * Email Validator Enable Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2026 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class EmailValidatorEnable implements Bootstrapper
{
    /**
     * {@inheritDoc}
     */
    public function bootstrap(Kernel $kernel)
    {
        EmailValidator::enable();
    }
}
