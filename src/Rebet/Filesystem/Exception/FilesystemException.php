<?php
namespace Rebet\Filesystem\Exception;

use League\Flysystem\FileNotFoundException as FlysystemFileNotFoundException;
use Rebet\Tools\Exception\RuntimeException;

/**
 * Filesystem Exception
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class FilesystemException extends RuntimeException
{
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }

    /**
     * Create exception from League\Flysystem\FileNotFoundException
     *
     * @param FlysystemFileNotFoundException $e
     * @return self
     */
    public static function from(FlysystemFileNotFoundException $e) : self
    {
        return new static($e->getMessage(), $e);
    }
}
