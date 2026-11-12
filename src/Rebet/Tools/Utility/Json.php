<?php

declare(strict_types=1);

namespace Rebet\Tools\Utility;

use stdClass;

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
    private function __construct() {}

    /**
     * Get the json serialize value.
     *
     * @param  mixed $value
     * @return mixed
     */
    public static function serialize($value)
    {
        return match (true) {
            $value === null                                       => null,
            $value instanceof \JsonSerializable                   => $value->jsonSerialize(),
            is_array($value)                                      => array_map(fn($v) => Json::serialize($v), $value),
            $value instanceof stdClass                            => array_map(fn($v) => Json::serialize($v), (array) $value),
            is_object($value) && method_exists($value, 'toArray') => array_map(fn($v) => Json::serialize($v), $value->toArray()),
            is_iterable($value)                                   => array_map(fn($v) => Json::serialize($v), iterator_to_array($value)),
            default                                               => $value,
        };
    }

    /**
     * Create digest string from JSON serialized text of given values.
     *
     * @param  string $algorithm hash algorithm for digest
     * @param  mixed  ...$values
     * @return string
     */
    public static function digest(string $algorithm, ...$values): string
    {
        return hash($algorithm, json_encode(static::serialize($values)));
    }
}
