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
     * @return mixed
     */
    public static function serialize($value)
    {
        switch (true) {
            case $value === null:
                return null;
            case $value instanceof \JsonSerializable:
                return $value->jsonSerialize();
            case is_array($value):
                return array_map(function ($v) { return Json::serialize($v); }, $value);
            case method_exists($value, 'toArray'):
                return array_map(function ($v) { return Json::serialize($v); }, $value->toArray());
            case $value instanceof \Traversable:
                return array_map(function ($v) { return Json::serialize($v); }, iterator_to_array($value));
            default:
                return $value;
        }
    }

    /**
     * Create digest string from JSON serialized text of given values.
     *
     * @param string $algorithm hash algorithm for digest
     * @param mixed ...$values
     * @return string
     */
    public static function digest(string $algorithm, ...$values) : string
    {
        return hash($algorithm, json_encode(static::serialize($values)));
    }
}
