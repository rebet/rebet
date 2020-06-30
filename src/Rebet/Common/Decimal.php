<?php
namespace Rebet\Common;

use Rebet\Common\Exception\LogicException;
use Rebet\Config\Configurable;

/**
 * Decimal Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Decimal
{
    use Configurable;

    public static function defaultConfig()
    {
        return [
            'mode'    => static::MODE_AUTO_PRECISION_SCALING,
            'options' => [
                'fixed_scale'  => 2,  // For MODE_FIXED_DECIMAL_PLACES
                'guard_digits' => 4,  // For MODE_FIXED_DECIMAL_PLACES / MODE_SIGNIFICANCE_ARITHMETIC
                'max_scale'    => 90, // For MODE_AUTO_PRECISION_SCALING
            ],
        ];
    }

    /**
     * Set significance arithmetic mode for all of calculations.
     *
     * @param int $mode Decimal::MODE_*
     * @param array $options for Decimal::MODE_* (default: [])
     *     - fixed_scale  : For MODE_FIXED_DECIMAL_PLACES
     *     - guard_digits : For MODE_FIXED_DECIMAL_PLACES / MODE_SIGNIFICANCE_ARITHMETIC
     *     - max_scale    : For MODE_AUTO_PRECISION_SCALING
     * @return void
     */
    public static function setMode(int $mode, array $options = []) : void
    {
        static::setConfig(['mode' => $mode, 'options' => $options]);
    }

    /**
     * Set the fixed scale option for Decimal::MODE_FIXED_DECIMAL_PLACES.
     *
     * @param integer $scale
     * @return void
     */
    public static function setFixedScale(int $fixed_scale) : void
    {
        static::setConfig(['options' => ['fixed_scale' => $fixed_scale]]);
    }

    /**
     * Set the guard digits option for Decimal::MODE_FIXED_DECIMAL_PLACES and Decimal::MODE_SIGNIFICANCE_ARITHMETIC.
     *
     * @param integer $guard_digits
     * @return void
     */
    public static function setGuardDigits(int $guard_digits) : void
    {
        static::setConfig(['options' => ['guard_digits' => $guard_digits]]);
    }

    /**
     * Set the max scale option for Decimal::MODE_AUTO_PRECISION_SCALING.
     *
     * @param integer $max_scale
     * @return void
     */
    public static function setMaxScale(int $max_scale) : void
    {
        static::setConfig(['options' => ['max_scale' => $max_scale]]);
    }

    /**
     * Auto Precision Scaling Mode (default mode)
     * ----------
     * In this mode, the module will try to maintain accuracy as much as possible in 'max_scale' precision by follow the rules below.
     *
     * [add]  Precision : Max scale of given operands (max_scale configure settings is the upper limit)
     *        Round Type: Decimal Places
     *          ex) 2 + 0.53 = 2.53
     * [sub]  Precision : Max scale of given operands (max_scale configure settings is the upper limit)
     *        Round Type: Decimal Places
     *          ex) 2 - 0.53 = 1.47
     * [mul]  Precision : Sum of scale of given operands (max_scale configure settings is the upper limit)
     *        Round Type: Decimal Places
     *          ex) 2 * 0.53 = 1.06
     * [sub]  Precision : max_scale configure settings and omit zero under decimal point
     *        Round Type: Decimal Places
     *          ex) 2 / 125          =        0.016
     *          ex) 2 /   3          =        0.666666666666666666666666666667
     *          ex) 2 /   0.00000003 = 66666666.666666666666666666666666666667
     *          ex) 2 /   0.003      =      666.666666666666666666666666666667
     *          ex) 2 /   0.004      =      500
     *          ex) 2 /   1e27       =        0.000000000000000000000000002
     *          ex) 2 /   1e33       =        0
     * [pow]  Precision : max_scale configure settings and omit zero under decimal point
     *        Round Type: Decimal Places
     *          ex) 8.21 ^  10 = 1391334554.52113004524890426201
     *          ex) 8.21 ^ -10 = 0.000000000718734395512932766524
     * [sqrt] Precision : max_scale configure settings and omit zero under decimal point
     *        Round Type: Decimal Places
     *          ex) √9 = 3
     *          ex) √2 = 1.414213562373095048801688724209
     *
     * @var int
     */
    const MODE_AUTO_PRECISION_SCALING = 1;

    /**
     * Significance Arithmetic Mode
     * ----------
     * In this mode, the module will try to significance arithmetic but adding 'guard_digits' to reduce roundoff error. by follow the rules below.
     *
     * [add]  Precision : Min scale of given operands plus guard digits
     *        Round Type: Decimal Places
     *          ex) 2 + 0.53 = 2.5000 to 1 s.f.
     * [sub]  Precision : Min scale of given operands plus guard digits
     *        Round Type: Decimal Places
     *          ex) 2 - 0.53 = 1.5000 to 1 s.f.
     * [mul]  Precision : Min significant figures of given operands plus guard digits
     *        Round Type: Significant Figures
     *          ex) 2 * 0.53 = 1.1000 to 1 s.f.
     * [sub]  Precision : Min significant figures of given operands plus guard digits
     *        Round Type: Significant Figures
     *          ex) 2 / 125          =        0.016000                                to 1 s.f.
     *          ex) 2 /   3          =        0.66667                                 to 1 s.f.
     *          ex) 2 /   0.00000003 = 66667000                                       to 1 s.f.
     *          ex) 2 /   0.003      =      666.67                                    to 1 s.f.
     *          ex) 2 /   0.004      =      500.00                                    to 1 s.f.
     *          ex) 2 /   1e27       =        0.0000000000000000000000000020000       to 1 s.f.
     *          ex) 2 /   1e33       =        0.0000000000000000000000000000000020000 to 1 s.f.
     * [pow]  Precision : Significant figures of base plus guard digits
     *        Round Type: Significant Figures
     *          ex) 8.21 ^  10 = 1391335000         to 3 s.f.
     *          ex) 8.21 ^ -10 = 0.0000000007187344 to 3 s.f.
     * [sqrt] Precision : Significant figures plus guard digits
     *        Round Type: Significant Figures
     *          ex) √9    = 3.0000   to 1 s.f.
     *          ex) √2    = 1.4142   to 1 s.f.
     *          ex) √2.00 = 1.414214 to 3 s.f.
     *
     * NOTE:
     *  - The zeros at the end of a number without a decimal point, it is considered valid.
     *    So '1000 to 4 s.f.', but if you use exponential notation then you will get '1.0e3 to 2 s.f.'.
     *
     * @var int
     */
    const MODE_SIGNIFICANCE_ARITHMETIC = 2;

    /**
     * Fixed Decimal Places Mode
     * ----------
     * In this mode, the module will try to keep precision that given 'fixed_scale' with 'guard_digits' by follow the rules below.
     *
     * When fixed scale is 3 then:
     * [add]  Precision: fixed scale configuration setting plus guard digits
     *        Round Type: Decimal Places
     *          ex) 2 + 0.53 = 2.530000 to 3 s.f.
     * [sub]  Precision: fixed scale configuration setting plus guard digits
     *        Round Type: Decimal Places
     *          ex) 2 - 0.53 = 1.470000 to 3 s.f.
     * [mul]  Precision: fixed scale configuration setting plus guard digits
     *        Round Type: Decimal Places
     *          ex) 2 * 0.53 = 1.060000 to 3 s.f.
     * [sub]  Precision: fixed scale configuration setting plus guard digits
     *        Round Type: Decimal Places
     *          ex) 2 / 125     =   0.016000 to 2 s.f.
     *          ex) 2 /   3     =   0.666667 to 2 s.f.
     *          ex) 2 /   0.003 = 666.666667 to 5 s.f.
     *          ex) 2 /   0.004 = 500.000000 to 5 s.f.
     *          ex) 2 / 1e27    =   0.000000 to 3 s.f.
     *          ex) 2 / 1e33    =   0.000000 to 3 s.f.
     * [pow]  Precision : fixed scale configuration setting plus guard digits
     *        Round Type: Decimal Places
     *          ex) 8.21 ^  10 = 1391334554.521130 to 12 s.f.
     *          ex) 8.21 ^ -10 = 0.000000 to 3 s.f.
     * [sqrt] Precision : fixed scale configuration setting plus guard digits
     *        Round Type: Decimal Places
     *          ex) √9 = 3.000000 to 3 s.f.
     *          ex) √2 = 1.414214  to 3 s.f.
     *
     * @var int
     */
    const MODE_FIXED_DECIMAL_PLACES = 3;

    /**
     * @var int Precision type decimal places for add/sub.
     */
    const TYPE_DECIMAL_PLACES = 1;

    /**
     * @var int Precision type significant figures for mul/div.
     */
    const TYPE_SIGNIFICANT_FIGURES = 2;

    /**
     * @var string of decimal value.
     */
    protected $value;

    /**
     * @var int of digits behind the point
     */
    protected $scale;

    /**
     * @var int of significant figure
     */
    protected $significant_figures;

    /**
     * Is the value was contaminated by float roundoff error.
     *
     * @var boolean
     */
    protected $is_dirty = false;

    /**
     * Create Decimal instance.
     *
     * @param string $value
     * @param string $decimal_point (default: '.')
     * @param string $thousands_separator (default: ',')
     */
    public function __construct(string $value, string $decimal_point = ".", string $thousands_separator = ",")
    {
        [$this->value, $this->scale, $this->significant_figures] = static::analyze($value, $decimal_point, $thousands_separator);
    }

    /**
     * Analyze exponential notation value and convert to real number format if needed.
     *
     * @param string $value
     * @param string $decimal_point (default: '.')
     * @param string $thousands_separator (default: ',')
     * @return array of [value, scale, significant_figures]
     * @throws InvalidArgumentException when the value format is invalid.
     */
    protected static function analyze(string $value, string $decimal_point = ".", string $thousands_separator = ",") : array
    {
        $value               = trim(str_replace([$decimal_point, $thousands_separator], ['.', ''], $value));
        $capture             = [];
        $significant_figures = null;
        if (preg_match('/^(?<sign>[+\-]?)(?<integer>[0-9]+)\.?(?<decimal>[0-9]+)?(?:[eE](?<exp>[+\-]?\d+))?(?: *\((?<sf>\d+) sf\))?$/u', $value, $capture)) {
            $sign    = $capture['sign'] ?? '';
            $sign    = $sign === '+' ? '' : $sign ;
            $integer = ltrim($capture['integer'] ?? '', '0');
            $integer = $integer === '' ? '0' : $integer ;
            $decimal = $capture['decimal'] ?? '';
            $exp     = intval($capture['exp'] ?? 0);
            $sf      = $capture['sf'] ?? null;
            $scale   = mb_strlen($decimal) - $exp;
            if ($scale <= 0) {
                $value = $sign.$integer.$decimal.str_repeat('0', abs($scale));
            } else {
                $value = str_repeat('0', max($scale - mb_strlen($integer.$decimal) + 1, 0)).$integer.$decimal;
                $value = $sign.substr($value, 0, mb_strlen($value) - $scale).'.'.substr($value, -1 * $scale);
            }
            $significant_figures = $sf ? intval($sf) : static::significantFiguresOf("{$integer}.{$decimal}") ;
        } else {
            throw new \InvalidArgumentException("Invalid value format, the value '{$value}' can not analyze.");
        }
        return [$value, static::scaleOf($value), $significant_figures ?? static::significantFiguresOf($value)];
    }

    /**
     * Get the decimal part digit count of given value.
     *
     * @param string $value
     * @return int
     */
    protected static function scaleOf(string $value) : int
    {
        return ($pos = strrpos($value, '.')) === false ? 0 : mb_strlen($value) - $pos - 1;
    }

    /**
     * Get the significant figures of given value.
     * Note: If the given value is zero then return the null constant as infinity.
     *
     * @param string $value
     * @return int
     */
    protected static function significantFiguresOf(string $value) : int
    {
        $value = preg_replace('/[^0-9.]/', '', $value);
        $scale = mb_strlen(str_replace('.', '', ltrim($value, '0.')));
        return $scale === 0 ?  1 + mb_strlen(Strings::ltrim(Strings::ltrim($value, '0'), '.')) : $scale ;
    }

    /**
     * Round $result by a precision for given formula and mode.
     *
     * @param int $mode of Decimal::MODE_*
     * @param Decimal $left
     * @param string $formula '+'|'-'|'*'|'/'|'^'|'√'
     * @param Decimal|null $right apply null when formula is '√'
     * @param Decimal $result
     * @param int $precision (default: null for apply mode rules)
     * @return self
     */
    protected static function roundBy(int $mode, Decimal $left, string $formula, ?Decimal $right, Decimal $result, ?int $precision = null) : self
    {
        if ($precision) {
            return $result->roundByDecimalPlaces($precision);
        }

        $max_scale    = static::config('options.max_scale');
        $guard_digits = static::config('options.guard_digits');
        switch ($mode) {
            case static::MODE_AUTO_PRECISION_SCALING:
                switch ($formula) {
                    case '+': return $result->roundByDecimalPlaces(max($left->scale, $right->scale));
                    case '-': return $result->roundByDecimalPlaces(max($left->scale, $right->scale));
                    case '*': return $result->roundByDecimalPlaces(min($left->scale + $right->scale, $max_scale));
                    case '/': return $result->roundByDecimalPlaces($max_scale)->compact();
                    case '^': return $result->roundByDecimalPlaces($max_scale)->compact();
                    case '√': return $result->roundByDecimalPlaces($max_scale)->compact();
                }
                throw new LogicException("Invalid formula [${$formula}] was given.");

            case static::MODE_SIGNIFICANCE_ARITHMETIC:
                switch ($formula) {
                    case '+': return $result->roundByDecimalPlaces(min($left->scale, $right->scale), $guard_digits);
                    case '-': return $result->roundByDecimalPlaces(min($left->scale, $right->scale), $guard_digits);
                    case '*': return $result->roundBySignificantFigures(min($left->significant_figures, $right->significant_figures), $guard_digits);
                    case '/': return $result->roundBySignificantFigures(min($left->significant_figures, $right->significant_figures), $guard_digits);
                    case '^': return $result->roundBySignificantFigures($left->significant_figures, $guard_digits);
                    case '√': return $result->roundBySignificantFigures($left->significant_figures, $guard_digits);
                }
                throw new LogicException("Invalid formula [${$formula}] was given.");

            case static::MODE_FIXED_DECIMAL_PLACES:
                $fixed_scale = static::config('options.fixed_scale');
                switch ($formula) {
                    case '+': return $result->roundByDecimalPlaces($fixed_scale, $guard_digits);
                    case '-': return $result->roundByDecimalPlaces($fixed_scale, $guard_digits);
                    case '*': return $result->roundByDecimalPlaces($fixed_scale, $guard_digits);
                    case '/': return $result->roundByDecimalPlaces($fixed_scale, $guard_digits);
                    case '^': return $result->roundByDecimalPlaces($fixed_scale, $guard_digits);
                    case '√': return $result->roundByDecimalPlaces($fixed_scale, $guard_digits);
                }
                throw new LogicException("Invalid formula [${$formula}] was given.");
       }

        throw new LogicException("Invalid mode [${$mode}] was given.");
    }

    /**
     * Create Decimal instance from given value.
     * NOTE: If the float value given then the value arbitrary precision could be lost when convert it to string.
     *
     * @param string|int|float|null $value
     * @param string $decimal_point (default: '.')
     * @param string $thousands_separator (default: ',')
     * @return self|null
     */
    public static function of($value, string $decimal_point = ".", string $thousands_separator = ",") : ?self
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof self) {
            return $value;
        }
        if (is_float($value)) {
            $value           = static::of(number_format($value, static::of((string)$value)->scale + static::config('options.guard_digits'), '.', ''))->compact();
            $value->is_dirty = true;
            return $value;
        }
        return new static((string)$value, $decimal_point, $thousands_separator);
    }

    /**
     * Get the value with guard digits.
     *
     * @param bool $with_guard_digits (default: true)
     * @return string
     */
    public function value(bool $with_guard_digits = true) : string
    {
        return $with_guard_digits ? $this->value : $this->roundBySignificantFigures($this->significant_figures, 0)->value;
    }

    /**
     * Get the scale of under decimal point.
     *
     * @param boolean $with_guard_digits (default: true)
     * @return integer
     */
    public function scale(bool $with_guard_digits = true) : int
    {
        return $with_guard_digits ? $this->scale : max($this->scale - $this->guardDigits(), 0) ;
    }

    /**
     * Get the significant figures of this value.
     *
     * @param boolean $with_guard_digits (default: true)
     * @return integer
     */
    public function significantFigures(bool $with_guard_digits = true) : int
    {
        return $with_guard_digits ? static::significantFiguresOf($this->value) : $this->significant_figures ;
    }

    /**
     * Get the guard digits of this value.
     *
     * @return int
     */
    public function guardDigits() : int
    {
        return max(static::significantFiguresOf($this->value) - $this->significant_figures, 0);
    }

    /**
     * Is the value was contaminated by float roundoff error.
     *
     * @return boolean
     */
    public function isDirty() : bool
    {
        return $this->is_dirty;
    }

    /**
     * Set the own dirty flag by given operands.
     *
     * @param Decimal ...$operands
     * @return self
     */
    protected function inheritDirtyFrom(Decimal ...$operands) : self
    {
        foreach ($operands as $operand) {
            if ($operand->is_dirty) {
                $this->is_dirty = true;
                return $this;
            }
        }
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        return $this->significant_figures === static::significantFiguresOf($this->value) ? $this->value : "{$this->value} ({$this->significant_figures} sf)";
    }

    /**
     * Omit zero under decimal point.
     *
     * @return self
     */
    public function compact() : self
    {
        return static::of($this->format(true, '.', ''))->inheritDirtyFrom($this);
    }

    /**
     * Add a thousand separator to the given number.
     * If you want to round scale, please call $decimal->round(2) first, then call format().
     *
     * @param bool $omit_zero under decimal point (default: false)
     * @param string $decimal_point (default: '.')
     * @param string $thousands_separator (default: ',')
     * @return string
     */
    public function format(bool $omit_zero = false, string $decimal_point = ".", string $thousands_separator = ",") : string
    {
        [$integer, $decimal] = Strings::split($this->value, '.', 2, '');
        $decimal             = $omit_zero ? Strings::rtrim($decimal, '0') : $decimal ;
        $integer             = preg_replace('/(\d)(?=(\d{3})+(?!\d))/', '$1'.$thousands_separator, $integer);
        return empty($decimal) ? $integer : "{$integer}{$decimal_point}{$decimal}" ;
    }

    /**
     * Perform arbitrary precision absolute value.
     *
     * @param string $value
     * @return self
     */
    public function abs() : self
    {
        return static::of(Strings::ltrim($this->value, '-'))->inheritDirtyFrom($this);
    }

    /**
     * Perform arbitrary precision comparison by bccomp().
     *
     * @param Decimal|string|int|null $other
     * @param integer|null $precision (default: null for max scale of operand)
     * @return int
     */
    public function comp($other, ?int $precision = null) : int
    {
        if ($other === null) {
            return 1;
        }
        $other     = static::of($other);
        $precision = $precision ?? max($this->scale, $other->scale) ;
        return \bccomp($this->value, $other->value, $precision);
    }

    /**
     * It checks left equals right by perform arbitrary precision comparison.
     *
     * @param Decimal|string|int|null $other
     * @param integer|null $precision (default: null for max scale of operand)
     * @return bool
     */
    public function eq($other, ?int $precision = null) : bool
    {
        return $this->comp($other, $precision) === 0;
    }

    /**
     * It checks left greater than right by perform arbitrary precision comparison.
     *
     * @param Decimal|string|int|null $other
     * @param integer|null $precision (default: null for max scale of operand)
     * @return bool
     */
    public function gt($other, ?int $precision = null) : bool
    {
        return $this->comp($other, $precision) === 1;
    }

    /**
     * It checks left greater equals right by perform arbitrary precision comparison.
     *
     * @param Decimal|string|int|null $other
     * @param integer|null $precision (default: null for max scale of operand)
     * @return bool
     */
    public function gte($other, ?int $precision = null) : bool
    {
        return $this->comp($other, $precision) !== -1;
    }

    /**
     * It checks left less than right by perform arbitrary precision comparison.
     *
     * @param Decimal|string|int|null $other
     * @param integer|null $precision (default: null for max scale of operand)
     * @return bool
     */
    public function lt($other, ?int $precision = null) : bool
    {
        return $this->comp($other, $precision) === -1;
    }

    /**
     * It checks left less equals right by perform arbitrary precision comparison.
     *
     * @param Decimal|string|int|null $other
     * @param integer|null $precision (default: null for max scale of operand)
     * @return bool
     */
    public function lte($other, ?int $precision = null) : bool
    {
        return $this->comp($other, $precision) !== 1;
    }

    /**
     * It checks the value is negative.
     *
     * @return boolean
     */
    public function isNegative() : bool
    {
        return $this->lt('0');
    }

    /**
     * Shift the decimal point to right (means $this * 10^{scale})
     *
     * @param integer $scale
     * @return self
     */
    public function shift(int $scale) : self
    {
        return static::of(bcmul($this->value, bcpow('10', $scale, abs(min($scale, 0))), $this->scale - $scale))->inheritDirtyFrom($this);
    }

    /**
     * Shift the decimal point to left (means $this * 10^-{scale})
     *
     * @param integer $scale
     * @return self
     */
    public function unshift(int $scale) : self
    {
        return $this->shift(-1 * $scale);
    }

    /**
     * Get the number string of upper the decimal point.
     *
     * @return string
     */
    public function integers() : string
    {
        return $this->scale === 0 ? $this->value : substr($this->value, 0, mb_strlen($this->value) - $this->scale - 1) ;
    }

    /**
     * Get the number string of under the decimal point.
     *
     * @return string
     */
    public function decimals() : string
    {
        return $this->scale === 0 ? '0' : substr($this->value, -1 * $this->scale) ;
    }

    /**
     * Floor the value.
     *
     * @param int $precision (default: 0)
     * @return self
     */
    public function floor(int $precision = 0) : self
    {
        $decimal = $this->shift($precision);
        $delta   = $decimal->isNegative() && bccomp($decimal->decimals(), '0') === 1 ? '-1' : '0' ;
        $decimal = static::of(\bcadd(Strings::ratrim($decimal->value, '.'), $delta))->inheritDirtyFrom($decimal);
        return $decimal->unshift($precision);
    }

    /**
     * Ceil the value.
     *
     * @param int $precision (default: 0)
     * @return self
     */
    public function ceil(int $precision = 0) : self
    {
        $decimal = $this->shift($precision);
        $delta   = !$decimal->isNegative() && bccomp($decimal->decimals(), '0') === 1 ? '1' : '0' ;
        $decimal = static::of(\bcadd(Strings::ratrim($decimal->value, '.'), $delta))->inheritDirtyFrom($decimal);
        return $decimal->unshift($precision);
    }

    /**
     * Round up the value by given precision.
     *
     * @param int $precision (default: 0)
     * @param int $guard_digits (default: 0)
     * @param int $precision_type (default: Decimal::TYPE_DECIMAL_PLACES)
     * @return self
     * @throws LogicException when invalid $precision_type given.
     */
    public function round(int $precision = 0, int $guard_digits = 0, int $precision_type = Decimal::TYPE_DECIMAL_PLACES) : self
    {
        switch ($precision_type) {
            case static::TYPE_DECIMAL_PLACES:
                return $this->roundByDecimalPlaces($precision, $guard_digits);
            case static::TYPE_SIGNIFICANT_FIGURES:
                return $this->roundBySignificantFigures($precision, $guard_digits);
        }
        throw new LogicException("Invalid precision type was given.");
    }

    /**
     * Remove guard digits by own significant figures using Decimal::TYPE_SIGNIFICANT_FIGURES rounding.
     *
     * @return self
     */
    public function normalize() : self
    {
        return $this->roundBySignificantFigures($this->significant_figures, 0);
    }

    /**
     * Round up the value by given decimal places precision.
     *
     * @param int $precision
     * @param int $guard_digits for reduce roundoff error. (default: 0)
     * @return self
     */
    protected function roundByDecimalPlaces(int $precision, int $guard_digits = 0) : self
    {
        $decimal = $this->shift($precision + $guard_digits);
        $delta   = $decimal->isNegative() ? '-0.5' : '0.5' ;
        $decimal = static::of(Strings::ratrim(\bcadd($decimal->value, $delta, $decimal->scale), '.'))->inheritDirtyFrom($decimal);
        $result  = $decimal->unshift($precision + $guard_digits);

        $result->significant_figures = $result->significant_figures - $guard_digits;
        return $result;
    }

    /**
     * Round up the value by given significant figures precision.
     *
     * @param int $precision
     * @param int $guard_digits for reduce roundoff error. (default: 0)
     * @return self
     */
    protected function roundBySignificantFigures(int $precision, int $guard_digits = 0) : self
    {
        if ($precision < 1) {
            throw new LogicException("Invalid significant figures precision [{$precision}] was given. Significant figures precision must be higher than 0.");
        }

        if ($this->eq('0')) {
            $result                      = static::of('0.'.str_repeat('0', $precision + $guard_digits - 1))->inheritDirtyFrom($this);
            $result->significant_figures = $precision;
            return $result;
        }

        $vs  = $this->scale;
        $vsf = $this->significantFigures();
        $gp  = $precision + $guard_digits;
        if ($vsf < $gp) {
            $result                      = static::of($this->value.($vs === 0 ? '.' : '').str_repeat('0', $gp - $vsf))->inheritDirtyFrom($this);
            $result->significant_figures = $precision;
            return $result;
        }

        $result                      = $this->roundByDecimalPlaces($this->lt('1') ? $gp + ($vs - $vsf) : $gp - ($vsf - $vs));
        $result->significant_figures = $precision;
        return $result;
    }

    /**
     * Perform arbitrary precision addition by bcadd().
     *
     * @param Decimal|string|int $other
     * @param int|null $precision (default: null for apply the mode rule)
     * @param int|null $mode of Decimal::MODE_* (default: depend on configure)
     * @return self
     */
    public function add($other, ?int $precision = null, ?int $mode = null) : self
    {
        $other  = static::of($other);
        $result = static::of(bcadd($this->value, $other->value, max($this->scale, $other->scale)))->inheritDirtyFrom($this, $other);
        return static::roundBy($mode ?? static::config('mode'), $this, '+', $other, $result, $precision);
    }

    /**
     * Perform arbitrary precision subtraction by bcsub().
     *
     * @param Decimal|string|int $other
     * @param int|null $precision (default: null for apply the mode rule)
     * @param int|null $mode of Decimal::MODE_* (default: depend on configure)
     * @return self
     */
    public function sub($other, ?int $precision = null, ?int $mode = null) : self
    {
        $other  = static::of($other);
        $result = static::of(bcsub($this->value, $other->value, max($this->scale, $other->scale)))->inheritDirtyFrom($this, $other);
        return static::roundBy($mode ?? static::config('mode'), $this, '-', $other, $result, $precision);
    }

    /**
     * Perform arbitrary precision multiplication by bcmul().
     *
     * @param Decimal|string|int $other
     * @param int|null $precision (default: null for apply the mode rule)
     * @param int|null $mode of Decimal::MODE_* (default: depend on configure)
     * @return self
     */
    public function mul($other, ?int $precision = null, ?int $mode = null) : self
    {
        $other  = static::of($other);
        $result = static::of(bcmul($this->value, $other->value, $this->scale + $other->scale))->inheritDirtyFrom($this, $other);
        return static::roundBy($mode ?? static::config('mode'), $this, '*', $other, $result, $precision);
    }

    /**
     * Perform arbitrary precision division by bcmul().
     *
     * @param Decimal|string|int $other
     * @param int|null $precision (default: null for apply the mode rule)
     * @param int|null $mode of Decimal::MODE_* (default: depend on configure)
     * @return self
     */
    public function div($other, ?int $precision = null, ?int $mode = null) : self
    {
        $other  = static::of($other);
        $result = static::of(bcdiv($this->value, $other->value, max(mb_strlen($other->integers()) + min($this->significant_figures, $other->significant_figures), static::config('options.max_scale')) + 2))->inheritDirtyFrom($this, $other);
        return static::roundBy($mode ?? static::config('mode'), $this, '/', $other, $result, $precision);
    }

    /**
     * Perform arbitrary precision power by bcpow().
     *
     * @param Decimal|string|int $exponent
     * @param int|null $precision (default: null for apply the mode rule)
     * @param int|null $mode of Decimal::MODE_* (default: depend on configure)
     * @return self
     */
    public function pow($exponent, ?int $precision = null, ?int $mode = null) : self
    {
        $exponent = static::of($exponent)->floor();
        $result   = static::of(bcpow($this->value, $exponent->value, $exponent->isNegative() ? static::config('options.max_scale') : $this->scale * abs(intval($exponent->value)) + 2))->inheritDirtyFrom($this, $exponent);
        return static::roundBy($mode ?? static::config('mode'), $this, '^', $exponent, $result, $precision);
    }

    /**
     * Perform arbitrary precision square root by bcsqrt().
     *
     * @param int|null $precision (default: null for apply the mode rule)
     * @param int|null $mode of Decimal::MODE_* (default: depend on configure)
     * @return self
     */
    public function sqrt(?int $precision = null, ?int $mode = null) : self
    {
        $result = static::of(bcsqrt($this->value, static::config('options.max_scale')))->inheritDirtyFrom($this);
        return static::roundBy($mode ?? static::config('mode'), $this, '^', null, $result, $precision);
    }

    /**
     * Perform arbitrary precision modulus by bcmod().
     * NOTE: The value and modulus can be only integer. For "float" decimal part will be ignored.
     *
     * @param Decimal|string|int $modulus
     * @return self|null
     */
    public function mod($modulus) : ?self
    {
        $modulus = static::of($modulus);
        return $modulus->eq(0, 0) ? null : static::of(bcmod($this->value, $modulus->integers()))->inheritDirtyFrom($this, $modulus);
    }

    /**
     * Perform arbitrary precision power and mond by bcpowmod().
     *
     * @param Decimal|string|int $exponent
     * @param Decimal|string|int $modulus
     * @return self
     */
    public function powmod($exponent, $modulus) : self
    {
        $exponent = static::of($exponent);
        $modulus  = static::of($modulus);
        return static::of(bcpowmod($this->integers(), $exponent->integers(), $modulus->integers()))->inheritDirtyFrom($this, $exponent, $modulus);
    }

    /**
     * Get min value in given values.
     *
     * @param array|Decimal|string|int|float ...$values
     * @return self
     */
    public static function min(...$values) : self
    {
        $min = null;
        foreach (is_array($values[0] ?? null) ? $values[0] : $values as $value) {
            $value = Decimal::of($value);
            if ($min === null) {
                $min = $value;
                continue;
            }
            if ($min->gt($value)) {
                $min = $value;
            }
        }
        return $min;
    }

    /**
     * Get max value in given values.
     *
     * @param array|Decimal|string|int|float ...$values
     * @return self
     */
    public static function max(...$values) : self
    {
        $max = null;
        foreach (is_array($values[0] ?? null) ? $values[0] : $values as $value) {
            $value = Decimal::of($value);
            if ($max === null) {
                $max = $value;
                continue;
            }
            if ($max->lt($value)) {
                $max = $value;
            }
        }
        return $max;
    }

    /**
     * Convert the decimal to int type.
     * NOTE: The result of this method may contain rounding errors.
     *
     * @return int
     */
    public function toInt() : int
    {
        return intval($this->value());
    }

    /**
     * Convert the decimal to float type.
     * NOTE: The result of this method may contain rounding errors.
     *
     * @return float
     */
    public function toFloat() : float
    {
        return floatval($this->value());
    }
}
