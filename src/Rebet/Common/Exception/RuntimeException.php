<?php
namespace Rebet\Common\Exception;

/**
 * Runtime Exception Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class RuntimeException extends \RuntimeException implements RebetException
{
    use RebetExceptionable;

    /**
     * Create a Runtime Exception
     *
     * @param string $message
     * @param \Throwable|null $previous (default: null)
     * @param int $code (default: 500)
     */
    public function __construct(string $message, ? \Throwable $previous = null, int $code = 500)
    {
        parent::__construct($message, $code, $previous);
    }
}
