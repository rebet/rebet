<?php
namespace Rebet\Common;

/**
 * Math Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Math
{
    /**
     * No instantiation
     */
    private function __construct()
    {
    }

    /**
     * Shift the given value decimal position then calc and unshift.
     *
     * @param string $value
     * @param integer $scale
     * @param callable $calc function(string $shifted_value) { ... }
     * @return string
     */
    protected static function shiftingCalc(string $value, int $scale, callable $calc) : string
    {
        $shifter = $scale === 0 ? '1' : bcpow('10', abs($scale));
        $shifter = $scale >= 0 ? $shifter : bcdiv('1', $shifter);
        return bcdiv($calc(bcmul($value, $shifter)), $shifter);
    }

    /**
     * It checks the given value is negative.
     *
     * @param string $value
     * @return boolean
     */
    public static function isNegative(string $value) : bool
    {
        return bccomp($value, '0') === -1;
    }

    /**
     * Get the number string of under the decimal point.
     *
     * @param string $value
     * @return string
     */
    protected static function decimalOf(string $value) : string
    {
        return Strings::contains($shifted, '.') ? Strings::rbtrim($shifted, '.') : '0' ;
    }

    /**
     * Floor the given value.
     *
     * @param string $value
     * @param int $scale (default: 0)
     * @return string
     */
    public static function floor(string $value, int $scale = 0) : string
    {
        return static::shiftingCalc($value, $scale, function ($shifted) {
            return Strings::ratrim($shifted, '.');
        });
    }

    /**
     * Round ip the given value.
     *
     * @param string $value
     * @param int $scale (default: 0)
     * @return string
     */
    public static function round(string $value, int $scale = 0) : string
    {
        return static::shiftingCalc($value, $scale, function ($shifted) use ($value) {
            $negative = static::isNegative($value);
            $first    = intval(static::decimalOf($shifted)[0]);
            $delta    = $first >= 5 ? ($negative ? '-1' : '1') : '0';
            return bcadd($shifted, $delta);
        });
    }

    /**
     * Ceil the given value.
     *
     * @param string $value
     * @param int $scale (default: 0)
     * @return string
     */
    public static function ceil(string $value, int $scale = 0) : string
    {
        return static::shiftingCalc($value, $scale, function ($shifted) {
            $delta = bccomp(static::decimalOf($shifted), '0') === 1 ? '1' : '0' ;
            return bcadd($shifted, $delta);
        });
    }
}
