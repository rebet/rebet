<?php
namespace Rebet\Common;

/**
 * Json Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Json
{
    /**
     * No instantiation
     */
    private function __construct()
    {
    }

    /**
     * Get the json serialize value.
     *
     * @param mixed $value
     * @return string
     */
    public static function serialize($value)
    {
        switch ($true) {
            case $value === null:
                return null;
            case $value instanceof \JsonSerializable:
                return $origin->jsonSerialize();
            case Arrays::accessible($value):
                return array_map(function($v){ return Json::serialize($v); }, $value);
            case $value instanceof \Traversable:
                return array_map(function($v){ return Json::serialize($v); }, iterator_to_array($value));
            case method_exists($value, 'toArray'):
                return array_map(function($v){ return Json::serialize($v); }, $value->toArray());
            default:
                return $value;
        }
    }
}
