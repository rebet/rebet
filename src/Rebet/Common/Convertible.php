<?php
namespace Rebet\Common;

/**
 * Type Convertible Interface
 *
 * It is an interface that "explicitly" indicates that type conversion by Reflector::convert() is possible.
 * Note that Reflector::convert($value, $type) does not necessarily have to implement this interface as it operates by determining the existence of a method.
 *
 * @see Rebet\Common\Reflector::convert
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
interface Convertible
{
    /**
     * Convert the type from other to self.
     * If conversion is not possible then return null.
     *
     * @param mixed $value
     * @return mixed
     */
    public static function valueOf($value);

    /**
     * Convert the type from self to other.
     * If conversion is not possible then return null.
     *
     * @param string $type
     * @return mixed
     */
    public function convertTo(string $type);
}
