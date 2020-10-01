<?php
namespace Rebet\Config\Exception;

use Rebet\Tools\Exception\RuntimeException;

/**
 * Config Not Define Exception Class
 *
 * Required It is thrown if the specified setting value is blank.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class ConfigNotDefineException extends RuntimeException
{
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }
}
