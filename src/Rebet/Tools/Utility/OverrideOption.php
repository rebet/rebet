<?php
namespace Rebet\Tools\Utility;

/**
 * Override Option Class
 *
 * @see Arrays::override()
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class OverrideOption
{
    /**
     * @var string of option on replace
     */
    public const REPLACE = '=';

    /**
     * @var string of option on prepend (Sequential array only)
     */
    public const PREPEND = '<';

    /**
     * @var string of option on apend (Sequential array only)
     */
    public const APPEND  = '>';

    /**
     * @var string of option on merge
     */
    public const MERGE = '+';

    /**
     * No instantiation
     */
    private function __construct()
    {
    }

    /**
     * Divides the specified key string into pure key names and options.
     *
     * @param string|int $key
     * @return array [string|int $key, string|null $option]
     */
    public static function split($key) : array
    {
        if (!is_string($key)) {
            return [$key, null];
        }

        foreach ([self::REPLACE, self::PREPEND, self::APPEND, self::MERGE] as $option) {
            if (Strings::endsWith($key, $option)) {
                return [Strings::rcut($key, mb_strlen($option)), $option];
            }
        }

        return [$key, null];
    }
}
