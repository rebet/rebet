<?php

namespace Rebet\Mail\Validator\Warning;

use Egulias\EmailValidator\Warning\Warning;

/**
 * Consecutive Dot Warning Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2020 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class ConsecutiveDotWarning extends Warning
{
    const CODE = 132;

    public function __construct()
    {
        $this->message = 'Concecutive DOT found';
    }
}
