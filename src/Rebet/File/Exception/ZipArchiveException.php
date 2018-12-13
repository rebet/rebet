<?php
namespace Rebet\File\Exception;

use Rebet\Common\Exception\RuntimeException;

/**
 * Zip Archive Exception
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class ZipArchiveException extends RuntimeException
{
    public function __construct(string $message, ?\Throwable $previous = null, int $code = 500)
    {
        parent::__construct($message, $previous, $code);
    }
}
