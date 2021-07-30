<?php

namespace Rebet\Mail\Validator\Warning;

use Egulias\EmailValidator\Warning\Warning;

/**
 * Dot At End Warning Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2020 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class DotAtEndWarning extends Warning
{
    const CODE = 142;

    public function __construct()
    {
        $this->message = 'Dot at the end';
    }
}
