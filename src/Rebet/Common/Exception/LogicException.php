<?php
namespace Rebet\Common\Exception;

/**
 * Logic Exception Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class LogicException extends \LogicException implements RebetException
{
    use RebetExceptionable;

    /**
     * Create a Logic Exception
     *
     * @param string $message
     * @param \Throwable|null $previous (default: null)
     */
    public function __construct(string $message, ? \Throwable $previous = null)
    {
        parent::__construct($message, null, $previous);
    }
}
