<?php
namespace Rebet\Common;

/**
 * Logic Exception Interface
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class LogicException extends \LogicException implements RebetException
{
    /**
     * Create a Logic Exception
     *
     * @param string $message
     * @param \Throwable|null $previous (default: null)
     */
    public function __construct(string $message, ? \Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
    
    /**
     * {@inheritDoc}
     */
    public static function by(string $message) : self
    {
        return new static($message);
    }

    /**
     * {@inheritDoc}
     */
    public function caused(\Throwable $previous) : self
    {
        Reflector::set($this, 'previous', $previous, true);
    }

    /**
     * {@inheritDoc}
     */
    public function code(int $code) : self
    {
        $this->code = $code;
    }
}
