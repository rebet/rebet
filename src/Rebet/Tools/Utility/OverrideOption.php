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
    public const REPLACE = '!';

    /**
     * @var string of option on prepend (Sequential array only)
     */
    public const PREPEND = '<';

    /**
     * @var string of option on apend (Sequential array only)
     */
    public const APEND = '>';

    /**
     * No instantiation
     */
    private function __construct()
    {
    }

    /**
     * Divides the specified key string into pure key names and options.
     *
     * @param string $key
     * @return array [string $key, string|null $option]
     */
    public static function split(string $key) : array
    {
        foreach ([self::REPLACE, self::PREPEND, self::APEND] as $option) {
            if (Strings::endsWith($key, $option)) {
                return [Strings::rcut($key, mb_strlen($option)), $option];
            }
        }

        return [$key, null];
    }
}
