<?php

namespace Rebet\Mail\Validator\Warning;

use Egulias\EmailValidator\Warning\Warning;

/**
 * Dot At Start Warning Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2020 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class DotAtStartWarning extends Warning
{
    const CODE = 141;

    public function __construct()
    {
        $this->message = 'Starts with a DOT';
    }
}
