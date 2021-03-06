<?php
namespace Rebet\Tools\Exception;

/**
 * Rebet Exception Interface
 *
 * @see RebetExceptionable trait
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
interface RebetException
{
    /**
     * Set the given previous exception.
     *
     * @param \Throwable $previous
     * @return self
     */
    public function caused(\Throwable $previous) : self ;

    /**
     * Get the previous exception.
     *
     * @return \Throwable|null
     */
    public function getCaused() : ?\Throwable ;

    /**
     * Set the given code
     *
     * @param mixed $code
     * @return self
     */
    public function code($code) : self ;

    /**
     * Set the given appendix data.
     *
     * @param mixed $appendix
     * @return self
     */
    public function appendix($appendix) : self ;
}
