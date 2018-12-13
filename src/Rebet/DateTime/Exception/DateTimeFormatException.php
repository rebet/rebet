<?php
namespace Rebet\DateTime\Exception;

use Rebet\Common\Exception\RuntimeException;

/**
 * Date Time Format Exception Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class DateTimeFormatException extends RuntimeException
{
    public function __construct(string $message, ?\Throwable $previous = null, int $code = 500)
    {
        parent::__construct($message, $previous, $code);
    }
}
