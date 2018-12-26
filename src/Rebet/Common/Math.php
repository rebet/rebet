<?php
namespace Rebet\Common;

/**
 * Math Class
 *
 * @todo implements
 *
 * Note: About Significant Figure
 *  - The zeros at the end of a number without a decimal point, it is considered valid.
 *  - The last 0 after the decimal point is actually present, but it is handled as omitted.
 *    ex)
 *    In add/sub: '2 + 0.53' should be '3' from the viewpoint of significant figures,
 *                but in this module the expression above treats like this '2.00 + 0.53 = 2.53'.
 *    In mul/div: '2 * 0.53' should be '1' from the viewpoint of significant figures,
 *                but in this module the expression above treats like this '2.00 * 0.53 = 1.1'.
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
     * Get the decimal part digit count of given value.
     *
     * @param string $value
     * @return integer
     */
    protected static function scaleOf(string $value) : int
    {
        return ($pos = strrpos($value, '.')) === false ? 0 : mb_strlen($value) - $pos - 1;
    }

    // /**
    //  * Get the integer part digit count of given value.
    //  *
    //  * @param string $value
    //  * @return integer
    //  */
    // protected static function digitOf(string $value) : int
    // {
    //     return mb_strlen(preg_replace('/[^0-9]/', '', Strings::ratrim($value, '.')));
    // }

    // /**
    //  * Undocumented function
    //  *
    //  * @param string $left
    //  * @param string $right
    //  * @return array [$left, $right, $scale]
    //  */
    // protected static function compensate(string $left, string $right) : array
    // {
    //     if (!Strings::contains($left, '.') && !Strings::contains($right, '.')) {
    //         return [$left, $right];
    //     }
    //     [$li, $ld] = array_pad(explode('.', $left), 2, '');
    //     [$ri, $rd] = array_pad(explode('.', $right), 2, '');
    //     $scale     = max(mb_strlen($ld), mb_strlen($rd));
    //     $ld        = str_pad($ld, $scale, '0', STR_PAD_RIGHT);
    //     $rd        = str_pad($rd, $scale, '0', STR_PAD_RIGHT);
    //     return ["{$li}.{$ld}", "{$ri}.{$rd}", $scale];
    // }

    // /**
    //  * Get the significant figure of given value.
    //  *
    //  * @param string $value
    //  * @return integer
    //  */
    // protected static function significantFigureOf(string $value) : int
    // {
    //     return mb_strlen(str_replace('.', '', ltrim($value, '0.')));
    // }

    // /**
    //  * Perform arbitrary double precision comparison by bccomp().
    //  *
    //  * @param string $left
    //  * @param string $right
    //  * @param integer|null $scale (default: keep scale all)
    //  * @return void
    //  */
    // public static function comp(string $left, string $right, ?int $scale = null)
    // {
    //     [$left, $right, $default_scale] = static::compensate($left, $right);
    //     return bccomp($left, $right, $scale ?? $default_scale);
    // }

    // /**
    //  * Perform arbitrary double precision addition by bcadd().
    //  *
    //  * @param string $left
    //  * @param string $right
    //  * @param integer|null $scale (default: keep scale all)
    //  * @return void
    //  */
    // public static function add(string $left, string $right, ?int $scale = null)
    // {
    //     [$left, $right, $default_scale] = static::compensate($left, $right);
    //     return bcadd($left, $right, $scale ?? $default_scale);
    // }

    // /**
    //  * Perform arbitrary double precision subtraction by bcsub().
    //  *
    //  * @param string $left
    //  * @param string $right
    //  * @param integer|null $scale (default: keep scale all)
    //  * @return void
    //  */
    // public static function sub(string $left, string $right, ?int $scale = null)
    // {
    //     [$left, $right, $default_scale] = static::compensate($left, $right);
    //     return bcsub($left, $right, $scale ?? $default_scale);
    // }

    // /**
    //  * Perform arbitrary double precision multiplication by bcmul().
    //  *
    //  * @param string $left
    //  * @param string $right
    //  * @param integer|null $scale (default: keep scale all)
    //  * @return void
    //  */
    // public static function mul(string $left, string $right, ?int $scale = null)
    // {
    //     return bcmul($left, $right, $scale ?? static::scaleOf($left) + static::scaleOf($right));
    // }

    // /**
    //  * Perform arbitrary double precision division by bcmul().
    //  *
    //  * @param string $left
    //  * @param string $right
    //  * @param integer|null $scale (default: keep scale all)
    //  * @return void
    //  */
    // public static function div(string $left, string $right, ?int $scale = null)
    // {
    //     return bcdiv($left, $right, $scale ?? static::scaleOf($left) + static::scaleOf($right));
    // }

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
        return bcdiv(
            $calc(
                bcmul($value, $shifter, max(static::scaleOf($value) - $scale, 0)),
                static::isNegative($value),
                max($scale, 0)
            ),
            $shifter,
            max($scale, 0)
        );
    }

    /**
     * It checks the given value is negative.
     *
     * @param string $value
     * @return boolean
     */
    public static function isNegative(string $value) : bool
    {
        return bccomp($value, '0', static::scaleOf($value)) === -1;
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
        return static::shiftingCalc($value, $scale, function ($shifted, $negative, $scale) {
            $delta = $negative && bccomp(static::decimalOf($shifted), '0') === 1 ? '-1' : '0' ;
            return bcadd(Strings::ratrim($shifted, '.'), $delta, $scale) ;
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
        return static::shiftingCalc($value, $scale, function ($shifted, $negative, $scale) {
            return bcadd($shifted, $negative ? '-0.5' : '0.5', $scale);
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
        return static::shiftingCalc($value, $scale, function ($shifted, $negative, $scale) {
            $delta = !$negative && bccomp(static::decimalOf($shifted), '0') === 1 ? '1' : '0' ;
            return bcadd(Strings::ratrim($shifted, '.'), $delta, $scale) ;
        });
    }

    /**
     * Add a thousand separator to the given number.
     *
     * @param string $value
     * @param int $scale (default: 0)
     * @param string $decimal_point (default: '.')
     * @param string $thousands_separator (default: ',')
     */
    public static function format(string $value, int $scale = 0, string $decimal_point = ".", string $thousands_separator = ",") : string
    {
        [$integer, $decimal] = Strings::split(static::round($value, $scale), '.', 2, '');
        $integer             = preg_replace('/(\d)(?=(\d{3})+(?!\d))/', '$1'.$thousands_separator, $integer);
        return empty($decimal) ? $integer : "{$integer}{$decimal_point}{$decimal}" ;
    }
}
