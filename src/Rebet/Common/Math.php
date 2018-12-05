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
        $shifter = bcpow('10', $scale, abs(min($scale, 0)));
        return bcdiv($calc(bcmul($value, $shifter, max(mb_strlen(static::decimalOf($value)) - $scale, 0))), $shifter, max($scale, 0));
    }

    /**
     * It checks the given value is negative.
     *
     * @param string $value
     * @return boolean
     */
    public static function isNegative(string $value) : bool
    {
        return bccomp($value, '0', mb_strlen(static::decimalOf($value))) === -1;
    }

    /**
     * Get the number string of under the decimal point.
     *
     * @param string $value
     * @return string
     */
    protected static function decimalOf(string $value) : string
    {
        return Strings::contains($value, '.') ? Strings::rbtrim($value, '.') : '0' ;
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
        return static::shiftingCalc($value, $scale, function ($shifted) use ($value, $scale) {
            $negative = static::isNegative($value);
            $decimal  = static::decimalOf($shifted);
            $delta    = bccomp($decimal, '0', mb_strlen($decimal)) === 1 ? -1 : 0 ;
            $delta    = $negative ? $delta : '0' ;
            return bcadd(Strings::ratrim($shifted, '.'), $delta) ;
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
        return static::shiftingCalc($value, $scale, function ($shifted) use ($value, $scale) {
            $negative = static::isNegative($value);
            $first    = intval(static::decimalOf($shifted)[0]);
            $delta    = $first >= 5 ? ($negative ? '-1' : '1') : '0';
            return bcadd($shifted, $delta, $scale > 0 ? $scale : 0);
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
        return static::shiftingCalc($value, $scale, function ($shifted) use ($scale) {
            $delta = bccomp(static::decimalOf($shifted), '0') === 1 ? '1' : '0' ;
            return bcadd($shifted, $delta, $scale > 0 ? $scale : 0);
        });
    }
}
