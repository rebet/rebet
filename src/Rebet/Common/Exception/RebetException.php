<?php
namespace Rebet\Common\Exception;

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
     * Create a Rebet Exception
     *
     * @param string $message
     * @return self
     */
    public static function by(string $message) : self ;
    
    /**
     * Set the given previous exception.
     *
     * @param \Throwable $previous
     * @return self
     */
    public function caused(\Throwable $previous) : self ;
    
    /**
     * Set the given code
     *
     * @param integer $code
     * @return self
     */
    public function code(int $code) : self ;

    /**
     * Set the given appendix data.
     *
     * @param mixed $appendix
     * @return self
     */
    public function appendix($appendix) : self ;
}
