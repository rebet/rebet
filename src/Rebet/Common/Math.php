<?php
namespace Rebet\Common;

use Rebet\Common\Exception\LogicException;
use Rebet\Config\Configurable;

/**
 * Math Class
 *
 * Note: About Significant Figure Rules
 *  - The zeros at the end of a number without a decimal point, it is considered valid.
 *  - The last 0 after the decimal point is actually present, but it is handled as omitted.
 *    ex)
 *    In add/sub: '2 + 0.53' should be '3' from the viewpoint of significant figures,
 *                but in this module the expression above treats like this '2.00 + 0.53 = 2.53'.
 *    In mul/div: '2 * 0.53' should be '1' from the viewpoint of significant figures,
 *                but in this module the expression above treats like this '2.00 * 0.53 = 1.06'.
 *                Note: '2.00 * 0.53' should be '1.1' becase of the effective figure of this expression is 2 digits,
 *                      but this module adding one significant digit to reduce rounding error.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Math
{
    use Configurable;

    public static function defaultConfig() : array
    {
        return [
            'default_precision' => null,
        ];
    }

    /**
     * @var int Precision type decimal places for add/sub/comp.
     */
    const TYPE_DECIMAL_PLACES = 1;

    /**
     * @var int Precision type significant figure for mul/div.
     */
    const TYPE_SIGNIFICANT_FIGURE = 2;

    /**
     * Set default precision for all of calculations (exclude comp methods).
     * Note: If set precision to null, that means using Significant Figure Rules of this modules
     *
     * @param integer|null $default_precision
     * @return void
     */
    public static function setDefaultPrecision(?int $default_precision) : void
    {
        static::setConfig(['default_precision' => $default_precision]);
    }

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
    public static function scaleOf(string $value) : int
    {
        return ($pos = strrpos($value, '.')) === false ? 0 : mb_strlen($value) - $pos - 1;
    }

    /**
     * Get the significant figure of given value.
     * Note: If the given value is zero then return the null constant as infinity.
     *
     * @param string $value
     * @return int
     */
    public static function significantFigureOf(string $value) : ?int
    {
        $scale = mb_strlen(str_replace('.', '', ltrim(preg_replace('/[^0-9.]/', '', $value), '0.')));
        return $scale === 0 ?  null : $scale ;
    }

    /**
     * Get the adopted significant figure.
     *
     * @param string $left
     * @param string $right
     * @return integer
     */
    protected static function adoptedSignificantFigure(string $left, string $right) : int
    {
        $l = static::significantFigureOf($left);
        $r = static::significantFigureOf($right);
        if ($l === null && $r === null) {
            return 1;
        }
        if ($l === null || $r === null) {
            return $l ?? $r;
        }
        return min($l, $r);
    }

    /**
     * Compensate the precision of given values.
     *
     * @param string $left
     * @param string $right
     * @return array [$left, $right, $scale, $significant_figure]
     */
    protected static function compensate(string $left, string $right) : array
    {
        if (!Strings::contains($left, '.') && !Strings::contains($right, '.')) {
            return [$left, $right, 0, static::adoptedSignificantFigure($left, $right)];
        }
        [$li, $ld] = Strings::split($left, '.', 2, '');
        [$ri, $rd] = Strings::split($right, '.', 2, '');
        $scale     = max(mb_strlen($ld), mb_strlen($rd));
        $ld        = str_pad($ld, $scale, '0', STR_PAD_RIGHT);
        $rd        = str_pad($rd, $scale, '0', STR_PAD_RIGHT);
        $left      = "{$li}.{$ld}";
        $right     = "{$ri}.{$rd}";
        return [$left, $right, $scale, static::adoptedSignificantFigure($left, $right)];
    }

    /**
     * Perform arbitrary precision addition by bcadd().
     *
     * @param string $left
     * @param string $right
     * @param integer|null $precision (default: depend on significant figure rules of this moudle)
     * @return void
     */
    public static function add(string $left, string $right, ?int $precision = null)
    {
        $precision = $precision ?? static::config('default_precision', false) ;
        if ($precision !== null) {
            return static::roundByDecimalPlaces(\bcadd($left, $right, $precision + 1), $precision);
        }
        [$left, $right, $scale, /*$significant_figure*/] = static::compensate($left, $right);
        return \bcadd($left, $right, $scale);
    }

    /**
     * Perform arbitrary precision subtraction by bcsub().
     *
     * @param string $left
     * @param string $right
     * @param integer|null $precision (default: depend on significant figure rules of this moudle)
     * @return void
     */
    public static function sub(string $left, string $right, ?int $precision = null)
    {
        $precision = $precision ?? static::config('default_precision', false) ;
        if ($precision !== null) {
            return static::roundByDecimalPlaces(\bcsub($left, $right, $precision + 1), $precision) ;
        }
        [$left, $right, $scale, /*$significant_figure*/] = static::compensate($left, $right);
        return \bcsub($left, $right, $scale);
    }

    /**
     * Perform arbitrary precision multiplication by bcmul().
     *
     * @param string $left
     * @param string $right
     * @param integer|null $precision (default: depend on significant figure rules of this moudle)
     * @return void
     */
    public static function mul(string $left, string $right, ?int $precision = null)
    {
        $precision = $precision ?? static::config('default_precision', false) ;
        if ($precision !== null) {
            return static::roundByDecimalPlaces(\bcmul($left, $right, $precision + 1), $precision);
        }

        [$left, $right, $scale, $significant_figure] = static::compensate($left, $right);
        return static::roundBySignificantFigures(\bcmul($left, $right, \max($scale, $significant_figure) * 2), $significant_figure + 1);
    }

    /**
     * Perform arbitrary precision division by bcmul().
     *
     * @param string $left
     * @param string $right
     * @param integer|null $precision (default: depend on significant figure rules of this moudle)
     * @return void
     */
    public static function div(string $left, string $right, ?int $precision = null)
    {
        $precision = $precision ?? static::config('default_precision', false) ;
        if ($precision !== null) {
            return static::roundByDecimalPlaces(bcdiv($left, $right, $precision + 1), $precision);
        }

        [$left, $right, $scale, $significant_figure] = static::compensate($left, $right);
        return static::roundBySignificantFigures(bcdiv($left, $right, \max($scale, $significant_figure) * 2), $significant_figure + 1);
    }

    /**
     * Perform arbitrary precision comparison by bccomp().
     *
     * @param string $left
     * @param string $right
     * @param integer|null $precision (default: depend on significant figure rules of this moudle)
     * @return void
     */
    public static function comp(string $left, string $right, ?int $precision = null)
    {
        if ($precision !== null) {
            return \bccomp($left, $right, $precision);
        }
        [$left, $right, $scale, /*$significant_figure*/] = static::compensate($left, $right);
        return \bccomp($left, $right, $scale) ;
    }
    
    /**
     * It checks left equals right by perform arbitrary precision comparison.
     *
     * @param string $left
     * @param string $right
     * @param integer|null $precision (default: depend on significant figure rules of this moudle)
     * @return bool
     */
    public static function eq(string $left, string $right, ?int $precision = null) : bool
    {
        return static::comp($left, $right, $precision) === 0;
    }
    
    /**
     * It checks left greater than right by perform arbitrary precision comparison.
     *
     * @param string $left
     * @param string $right
     * @param integer|null $precision (default: depend on significant figure rules of this moudle)
     * @return bool
     */
    public static function gt(string $left, string $right, ?int $precision = null) : bool
    {
        return static::comp($left, $right, $precision) === 1;
    }

    /**
     * It checks left greater equals right by perform arbitrary precision comparison.
     *
     * @param string $left
     * @param string $right
     * @param integer|null $precision (default: depend on significant figure rules of this moudle)
     * @return bool
     */
    public static function gte(string $left, string $right, ?int $precision = null) : bool
    {
        return static::comp($left, $right, $precision) !== -1;
    }

    /**
     * It checks left less than right by perform arbitrary precision comparison.
     *
     * @param string $left
     * @param string $right
     * @param integer|null $precision (default: depend on significant figure rules of this moudle)
     * @return bool
     */
    public static function lt(string $left, string $right, ?int $precision = null) : bool
    {
        return static::comp($left, $right, $precision) === -1;
    }

    /**
     * It checks left less equals right by perform arbitrary precision comparison.
     *
     * @param string $left
     * @param string $right
     * @param integer|null $precision (default: depend on significant figure rules of this moudle)
     * @return bool
     */
    public static function lte(string $left, string $right, ?int $precision = null) : bool
    {
        return static::comp($left, $right, $precision) !== 1;
    }

    /**
     * Shift the given value decimal position then calc and unshift.
     *
     * @param string $value
     * @param integer $precision
     * @param callable $calc function(string $shifted_value) { ... }
     * @return string
     */
    protected static function shiftingCalc(string $value, int $precision, callable $calc) : string
    {
        $shifter = \bcpow('10', $precision, \abs(min($precision, 0)));
        return \bcdiv(
            $calc(
                \bcmul($value, $shifter, \max(static::scaleOf($value) - $precision, 0)),
                static::isNegative($value),
                \max($precision, 0)
            ),
            $shifter,
            \max($precision, 0)
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
        return \bccomp($value, '0', static::scaleOf($value)) === -1;
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
     * @param int|null $precision (default: depend on default_precision or 0)
     * @return string
     */
    public static function floor(string $value, ?int $precision = null) : string
    {
        $precision = $precision ?? static::config('default_precision', false) ?? 0;
        return static::shiftingCalc($value, $precision, function ($shifted, $negative, $precision) {
            $delta = $negative && \bccomp(static::decimalOf($shifted), '0') === 1 ? '-1' : '0' ;
            return \bcadd(Strings::ratrim($shifted, '.'), $delta, $precision) ;
        });
    }

    /**
     * Round up the value by given decimal part precision.
     *
     * @param string $value
     * @param int|null $precision (default: depend on default_precision or 0)
     * @param int $precision_type (default: TYPE_DECIMAL_PLACES)
     * @return string
     */
    public static function round(string $value, ?int $precision = null, ?int $precision_type = null) : string
    {
        $precision = $precision ?? static::config('default_precision', false) ?? 0;
        switch ($precision_type ?? static::TYPE_DECIMAL_PLACES) {
            case static::TYPE_DECIMAL_PLACES:
                return static::roundByDecimalPlaces($value, $precision);
            case static::TYPE_SIGNIFICANT_FIGURE:
                return static::roundBySignificantFigures($value, $precision);
        }
        return LogicException::by("Invalid precision type was given.");
    }

    /**
     * Round up the value by given decimal places precision.
     *
     * @param string $value
     * @param int $precision (default: depend on default_precision or 0)
     * @return string
     */
    protected static function roundByDecimalPlaces(string $value, ?int $precision = null) : string
    {
        $precision = $precision ?? static::config('default_precision', false) ?? 0;
        return static::shiftingCalc($value, $precision, function ($shifted, $negative, $precision) {
            return \bcadd($shifted, $negative ? '-0.5' : '0.5', $precision);
        });
    }

    /**
     * Round up the value by given significant figure precision.
     *
     * @param string $value
     * @param integer $precision
     * @return string
     */
    public static function roundBySignificantFigures(string $value, int $precision) : string
    {
        if ($precision < 1) {
            throw LogicException::by("Invalid significant figure precision [{$precision}] was given. Significant figure precision must be higher than 0.");
        }

        if (static::eq($value, '0')) {
            return '0.'.str_repeat('0', $precision);
        }

        $vsf = static::significantFigureOf($value) ?? 1;
        if ($vsf === $precision) {
            return $value;
        }
        if ($vsf < $precision) {
            return Strings::contains($value, '.') ? $value.str_repeat('0', $precision - $vsf) : $value.'.'.str_repeat('0', $precision - $vsf) ;
        }

        $vs  = static::scaleOf($value);
        if (static::lt($value, '1')) {
            return static::roundByDecimalPlaces($value, $precision + ($vs - $vsf));
        }
        
        return static::roundByDecimalPlaces($value, $precision - ($vsf - $vs));
    }

    /**
     * Ceil the given value.
     *
     * @param string $value
     * @param int $precision (default: depend on default_precision or 0)
     * @return string
     */
    public static function ceil(string $value, ?int $precision = null) : string
    {
        $precision = $precision ?? static::config('default_precision', false) ?? 0;
        return static::shiftingCalc($value, $precision, function ($shifted, $negative, $precision) {
            $delta = !$negative && \bccomp(static::decimalOf($shifted), '0') === 1 ? '1' : '0' ;
            return \bcadd(Strings::ratrim($shifted, '.'), $delta, $precision) ;
        });
    }

    /**
     * Add a thousand separator to the given number.
     *
     * @param string $value
     * @param int $precision (default: depend on default_precision or 0)
     * @param string $decimal_point (default: '.')
     * @param string $thousands_separator (default: ',')
     */
    public static function format(string $value, ?int $precision = null, string $decimal_point = ".", string $thousands_separator = ",") : string
    {
        $precision           = $precision ?? static::config('default_precision', false) ?? 0;
        [$integer, $decimal] = Strings::split(static::round($value, $precision), '.', 2, '');
        $integer             = preg_replace('/(\d)(?=(\d{3})+(?!\d))/', '$1'.$thousands_separator, $integer);
        return empty($decimal) ? $integer : "{$integer}{$decimal_point}{$decimal}" ;
    }
}
