<?php
namespace Rebet\Common\Exception;

/**
 * Rebet Exception Interface
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
interface RebetException
{
    public static function by(string $message) : self ;
    
    public function caused(\Throwable $previous) : self ;
    
    public function code(int $code) : self ;

    public function appendix($appendix) : self ;
}
