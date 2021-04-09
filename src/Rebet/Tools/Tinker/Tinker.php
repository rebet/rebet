<?php
namespace Rebet\Tools\Tinker;

use Rebet\Tools\Config\Configurable;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\Exception\LogicException;
use Rebet\Tools\Math\Decimal;
use Rebet\Tools\Reflection\Reflector;
use Rebet\Tools\Utility\Arrays;
use Rebet\Tools\Utility\Json;
use Rebet\Tools\Utility\Strings;
use Rebet\Tools\Utility\Utils;

/**
 * Tinker Class
 *
 * @method Tinker|bool convert(?string $type)                                                                                                                Call Reflector::convert($value, ...)
 * @method bool        isBlank()                                                                                                                             Call Utils::isBlank($value)
 * @method Tinker|bool bvl($default)                                                                                                                         Call Utils::bvl($value, ...)
 * @method bool        isEmpty()                                                                                                                             Call Utils::isEmpty($value)
 * @method Tinker|bool evl($default)                                                                                                                         Call Utils::evl($value, ...)
 * @method Tinker      lcut(int $length, string $encoding = 'UTF-8')                                                                                         Call Strings::lcut($value, ...)
 * @method Tinker      rcut(int $length, string $encoding = 'UTF-8')                                                                                         Call Strings::rcut($value, ...)
 * @method Tinker      clip(int $length, string $ellipsis = '...')                                                                                           Call Strings::clip($value, ...)
 * @method Tinker      indent(string $char = "\t", int $depth = 1)                                                                                           Call Strings::indent($value, ...)
 * @method Tinker      ltrim(string $prefix = ' ', ?int $max = null)                                                                                         Call Strings::ltrim($value, ...)
 * @method Tinker      rtrim(string $suffix = ' ', ?int $max = null)                                                                                         Call Strings::rtrim($value, ...)
 * @method Tinker      trim(string $deletion = ' ', ?int $max = null)                                                                                        Call Strings::trim($value, ...)
 * @method Tinker      mbtrim()                                                                                                                              Call Strings::mbtrim($value)
 * @method bool        startsWith(string $needle)                                                                                                            Call Strings::startsWith($value, ...)
 * @method bool        endsWith(string $needle)                                                                                                              Call Strings::endsWith($value, ...)
 * @method bool        contains(string|string[] $searches, ?int $at_least = null)                                                                            Call Strings::contains($value, ...)
 * @method bool        match(string|string[] $patterns)                                                                                                      Call Strings::match($value, ...)
 * @method bool        wildmatch(string|string[] $patterns)                                                                                                  Call Strings::wildmatch($value, ...)
 * @method Tinker      split(string $delimiter, int $size, $padding = null)                                                                                  Call Strings::split($value, ...)
 * @method Tinker      pluck(int|string|\Closure|null $value_field, int|string|\Closure|null $key_field = null)                                              Call Arrays::pluck($value, ...) - Closure : `function($index, $key, $value) {...}`
 * @method Tinker      override($diff, array|string $option = [], string $default_array_override_option = OverrideOption::APPEND, ?\Closure $handler = null) Call Arrays::override($value, ...) - Closure : `function($index, $key, $value) {...}`
 * @method Tinker      duplicate()                                                                                                                           Call Arrays::duplicate($value)
 * @method Tinker      crossJoin(iterable ...$arrays)                                                                                                        Call Arrays::crossJoin($value, ...)
 * @method Tinker      only(array|string $keys)                                                                                                              Call Arrays::only($value, ...)
 * @method Tinker      except(array|string $keys)                                                                                                            Call Arrays::except($value, ...)
 * @method Tinker      where(?callable $callback)                                                                                                            Call Arrays::where($value, ...) - $callback : `function($value[, $key]) : bool {...}`
 * @method Tinker      compact()                                                                                                                             Call Arrays::compact($value)
 * @method Tinker      unique(int $sort_flag = SORT_REGULAR)                                                                                                 Call Arrays::unique($value, ...)
 * @method Tinker|bool first(?callable $callback = null, $default = null)                                                                                    Call Arrays::first($value, ...) - $callback : `function($value[, $key]) : bool {...}`
 * @method Tinker|bool last(?callable $callback = null, $default = null)                                                                                     Call Arrays::last($value, ...) - $callback : `function($value[, $key]) : bool {...}`
 * @method Tinker      flatten(int $depth = INF)                                                                                                             Call Arrays::flatten($value, ...)
 * @method Tinker      prepend($value, $key = null)                                                                                                          Call Arrays::prepend($value, ...)
 * @method Tinker      shuffle(?int $seed = null)                                                                                                            Call Arrays::shuffle($value, ...)
 * @method Tinker      map(callable $callback)                                                                                                               Call Arrays::map($value, ...) - $callback : `function($value[, $key]) {...}`
 * @method Tinker|bool reduce(callable $reducer, $initial = null)                                                                                            Call Arrays::reduce($value, ...) - $reducer : `function($carry, $item) {...}`
 * @method Tinker      diff($items, ?callable $comparator = null)                                                                                            Call Arrays::diff($value, ...) - $comparator : `function($a, $b) : int {...}`
 * @method Tinker      intersect($items, ?callable $comparator = null)                                                                                       Call Arrays::intersect($value, ...) - $comparator : `function($a, $b) : int {...}`
 * @method bool        every(callable $test)                                                                                                                 Call Arrays::every($value, ...) - $test : `function($v, $k) : bool {...}`
 * @method Tinker      groupBy(callable|string|array $group_by = null, bool $preserve_keys = false)                                                          Call Arrays::groupBy($value, ...) - $group_by : `function($value, $key) {...}`
 * @method Tinker      union($other)                                                                                                                         Call Arrays::union($value, ...)
 * @method Tinker|bool min(callable|string|null $retriever = null, $initial = null)                                                                          Call Arrays::min($value, ...) - $retriever : `function($value) {...}`
 * @method Tinker|bool max(callable|string|null $retriever = null, $initial = null)                                                                          Call Arrays::max($value, ...) - $retriever : `function($value) {...}`
 * @method Tinker      sort(int $order = SORT_ASC, callable|int $comparator = SORT_REGULAR)                                                                  Call Arrays::sort($value, ...) - $comparator : `function($a, $b) : int`
 * @method Tinker      sortBy(callable|string $retriever, int $order = SORT_ASC, callable|int $comparator = SORT_REGULAR)                                    Call Arrays::sortBy($value, ...) - $retriever : `function($value) {...}` $comparator :  `function($a, $b) : int {...}`
 * @method Tinker      sortKeys(int $order = SORT_ASC, callable|int $comparator = SORT_REGULAR)                                                              Call Arrays::sortKeys($value, ...) - $comparator : `function($a, $b) : int`
 * @method Tinker      sum(callable|string|null $retriever = null, bool $arbitrary_precision = false, ?int $precision = null)                                Call Arrays::sum($value, ...) - $retriever : `function($value) {...}`
 * @method Tinker      avg(callable|string|null $retriever = null, bool $arbitrary_precision = false, ?int $precision = null)                                Call Arrays::avg($value, ...) - $retriever : `function($value) {...}`
 * @method Tinker      median(callable|string|null $retriever = null, bool $arbitrary_precision = false, ?int $precision = null)                             Call Arrays::median($value, ...) - $retriever : `function($value) {...}`
 * @method Tinker      mode(callable|string|null $retriever = null)                                                                                          Call Arrays::mode($value, ...) - $retriever : `function($value) {...}`
 * @method Tinker      implode(string $delimiter = ', ')                                                                                                     Call Arrays::implode($value, ...)
 * @method Tinker      nvl($default)                                                                                                                         Call Tinker.filter.customs.nvl($value, ...) configured closure - Return the given default value if the wrapped value is null.
 * @method Tinker      default($default)                                                                                                                     Call Tinker.filter.customs.default($value, ...) configured closure - Return the given default value if the wrapped value is null.
 * @method Tinker      escape(string $type = 'html')                                                                                                         Call Tinker.filter.customs.escape($value, ...) configured closure - Escape the wrapped value string by html sanitise or url encoding. $type : `'html'|'url'`
 * @method Tinker      nl2br()                                                                                                                               Call Tinker.filter.customs.nl2br($value) configured closure - Convert line feed to <br> tag using PHP nl2br() function.
 * @method Tinker      datetimef(string $format)                                                                                                             Call Tinker.filter.customs.datetimef($value, ...) configured closure - Format the wrapped value using DateTime::format().
 * @method Tinker      numberf(int $precision = 0, bool $omit_zero = false, string $decimal_point = '.', string $thousands_separator = ',')                  Call Tinker.filter.customs.numberf($value, ...) configured closure - Format the wrapped value using Decimal::round($precision)->format($omit_zero, $decimal_point, $thousands_separator).
 * @method Tinker      stringf(string $format)                                                                                                               Call Tinker.filter.customs.stringf($value, ...) configured closure - Format the wrapped value using sprintf format.
 * @method Tinker      explode(string $delimiter, int $limit = PHP_INT_MAX)                                                                                  Call Tinker.filter.customs.explode($value, ...) configured closure - Split the wrapped value string using given delimiter.
 * @method Tinker      replace($pattern, $replacement, int $limit = -1)                                                                                      Call Tinker.filter.customs.replace($value, ...) configured closure - Replace the string using given regex pattern from the wrapped value.
 * @method Tinker      lower()                                                                                                                               Call Tinker.filter.customs.lower($value) configured closure - Change to lower case string from the wrapped value.
 * @method Tinker      upper()                                                                                                                               Call Tinker.filter.customs.upper($value) configured closure - Change to upper case string from the wrapped value.
 * @method Tinker      decimal()                                                                                                                             Call Tinker.filter.customs.decimal($value) configured closure - Convert to Decimal class from the wrapped value.
 * @method Tinker      abs()                                                                                                                                 Call Tinker.filter.customs.abs($value) configured closure - Calc the wrapped value using Decimal::abs().
 * @method bool        eq($other, ?int $precision = null)                                                                                                    Call Tinker.filter.customs.eq($value, ...) configured closure - Compare the wrapped value and given $value using Decimal::eq(). Returns false if null is included.
 * @method bool        gt($other, ?int $precision = null)                                                                                                    Call Tinker.filter.customs.gt($value, ...) configured closure - Compare the wrapped value and given $value using Decimal::gt(). Returns false if null is included.
 * @method bool        gte($other, ?int $precision = null)                                                                                                   Call Tinker.filter.customs.gte($value, ...) configured closure - Compare the wrapped value and given $value using Decimal::gte(). Returns false if null is included.
 * @method bool        lt($other, ?int $precision = null)                                                                                                    Call Tinker.filter.customs.lt($value, ...) configured closure - Compare the wrapped value and given $value using Decimal::lt(). Returns false if null is included.
 * @method bool        lte($other, ?int $precision = null)                                                                                                   Call Tinker.filter.customs.lte($value, ...) configured closure - Compare the wrapped value and given $value using Decimal::lte(). Returns false if null is included.
 * @method Tinker      add($other, ?int $precision = null, ?int $mode = null)                                                                                Call Tinker.filter.customs.add($value, ...) configured closure - Calc the wrapped value and given $value using Decimal::add().
 * @method Tinker      sub($other, ?int $precision = null, ?int $mode = null)                                                                                Call Tinker.filter.customs.sub($value, ...) configured closure - Calc the wrapped value and given $value using Decimal::sub().
 * @method Tinker      mul($other, ?int $precision = null, ?int $mode = null)                                                                                Call Tinker.filter.customs.mul($value, ...) configured closure - Calc the wrapped value and given $value using Decimal::mul().
 * @method Tinker      div($other, ?int $precision = null, ?int $mode = null)                                                                                Call Tinker.filter.customs.div($value, ...) configured closure - Calc the wrapped value and given $value using Decimal::div().
 * @method Tinker      pow($other, ?int $precision = null, ?int $mode = null)                                                                                Call Tinker.filter.customs.pow($value, ...) configured closure - Calc the wrapped value and given $value using Decimal::pow().
 * @method Tinker      sqrt(?int $precision = null, ?int $mode = null)                                                                                       Call Tinker.filter.customs.sqrt($value, ...) configured closure - Calc the wrapped value using Decimal::sqrt().
 * @method Tinker      mod($modulus)                                                                                                                         Call Tinker.filter.customs.mod($value, ...) configured closure - Calc the wrapped value using Decimal::mod().
 * @method Tinker      powmod($exponent, $modulus)                                                                                                           Call Tinker.filter.customs.powmod($value, ...) configured closure - Calc the wrapped value using Decimal::powmod().
 * @method Tinker      floor(int $precision = 0)                                                                                                             Call Tinker.filter.customs.floor($value, ...) configured closure - Decimal::floor($value, ...) the wrapped value.
 * @method Tinker      round(int $precision = 0, int $guard_digits = 0, int $precision_type = Decimal::TYPE_DECIMAL_PLACES)                                  Call Tinker.filter.customs.round($value, ...) configured closure - Decimal::round($value, ...) the wrapped value.
 * @method Tinker      ceil(int $precision = 0)                                                                                                              Call Tinker.filter.customs.ceil($value, ...) configured closure - Decimal::ceil($value, ...) the wrapped value.
 * @method Tinker      dump()                                                                                                                                Call Tinker.filter.customs.dump($value) configured closure - Dump wrapped value as string for debug and log.
 * @method Tinker      invoke(...$args)                                                                                                                      Call Tinker.filter.customs.invoke($value, ...) configured closure - Invoke wrraped callback function.
 * @method bool        equals($other)                                                                                                                        Call Tinker.filter.customs.equals($value, ...) configured closure - It will compare using '==' operator.
 * @method bool        sameAs($other)                                                                                                                        Call Tinker.filter.customs.sameAs($value, ...) configured closure - It will compare using '===' operator.
 * @method Tinker      nnvl($then, $else = null)                                                                                                             Call Tinker.filter.customs.nnvl($value, ...) configured closure - Return given value if the wrapped value is NOT null.
 * @method Tinker      nbvl($then, $else = null)                                                                                                             Call Tinker.filter.customs.nnbl($value, ...) configured closure - Return given value if the wrapped value is NOT blank(= null,'',[]).
 * @method Tinker      nevl($then, $else = null)                                                                                                             Call Tinker.filter.customs.nnel($value, ...) configured closure - Return given value if the wrapped value is NOT empty(= null,'',[], 0).
 * @method Tinker      when(mixed $test, $then, $else = null)                                                                                                Call Tinker.filter.customs.when($value, ...) configured closure - Return given value if the wrapped value matched given test. - $test : value or `function($value) {...}`
 * @method Tinker      case(array $map, $default = null)                                                                                                     Call Tinker.filter.customs.case($value, ...) configured closure - Return given case value if the wrapped value matched given case key.
 * @method Tinker      length()                                                                                                                              Call Tinker.filter.customs.length($value, ...) configured closure - Get the wrapped value string length.
 * @method Tinker      values()                                                                                                                              Call Tinker.filter.customs.values($value, ...) configured closure - Get values from the wrapped value.
 * @method Tinker      keys()                                                                                                                                Call Tinker.filter.customs.keys($value, ...) configured closure - Get keys from the wrapped value.
 * @method bool        isNull()                                                                                                                              Call PHP function is_null($value) - It checks the wrapped value is null or not.
 * @method bool        isString()                                                                                                                            Call PHP function is_string($value) - It checks the wrapped value is string or not.
 * @method bool        isInt()                                                                                                                               Call PHP function is_int($value) - It checks the wrapped value is int or not.
 * @method bool        isFloat()                                                                                                                             Call PHP function is_float($value) - It checks the wrapped value is float or not.
 * @method bool        isArray()                                                                                                                             Call PHP function is_array($value) - It checks the wrapped value is array or not.
 * @method bool        isBool()                                                                                                                              Call PHP function is_bool($value) - It checks the wrapped value is bool or not.
 * @method bool        isCallable()                                                                                                                          Call PHP function is_callable($value) - It checks the wrapped value is callable or not.
 *
 * And you can call any PHP function xxx_yyy($value [, $arg, ...]) as xxxYyy([$arg, ...]).
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Tinker implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable
{
    use Configurable;

    public static function defaultConfig()
    {
        return [
            'filter' => [
                'delegaters' => [
                    Reflector::class => ['convert'],
                    Utils::class     => ['isBlank', 'bvl', 'isEmpty', 'evl'],
                    Strings::class   => ['lcut', 'rcut', 'clip', 'indent', 'ltrim', 'rtrim', 'trim', 'mbtrim', 'startsWith', 'endsWith', 'contains', 'match', 'wildmatch', 'split'],
                    Arrays::class    => [
                        'pluck', 'override', 'duplicate', 'crossJoin', 'only', 'except', 'where', 'compact', 'unique',
                        'first', 'last', 'flatten', 'prepend', 'shuffle', 'map', 'reduce', 'diff', 'intersect',
                        'every', 'groupBy', 'union', 'min', 'max', 'sort', 'sortBy', 'sortKeys', 'sum', 'avg',
                        'median', 'mode', 'implode', 'toQuery'
                    ],
                ],
                'customs' => [
                    // You can use php built-in functions as filters when the 1st argument is for value.
                    'nvl'      => function ($value, $default) { return $value ?? $default; },
                    'default'  => function ($value, $default) { return $value ?? $default; },
                    'escape'   => function (string $value, string $type = 'html') {
                        switch ($type) {
                            case 'html': return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                            case 'url': return urlencode($value);
                            default: throw new \InvalidArgumentException("Invalid escape type [{$type}] given. The type must be html or url");
                        }
                    },
                    'nl2br'     => function (string $value) { return nl2br($value); },
                    'datetimef' => function (DateTime $value, string $format) { return $value->format($format); },
                    'numberf'   => function ($value, int $precision = 0, bool $omit_zero = false, string $decimal_point = '.', string $thousands_separator = ',') { return $value === null ? null : Decimal::of($value)->round($precision)->format($omit_zero, $decimal_point, $thousands_separator); },
                    'stringf'   => function ($value, string $format) { return $value === null ? null : sprintf($format, $value) ; },
                    'explode'   => function (string $value, string $delimiter, int $limit = PHP_INT_MAX) { return explode($delimiter, $value, $limit); },
                    'replace'   => function (string $value, $pattern, $replacement, int $limit = -1) { return preg_replace($pattern, $replacement, $value, $limit); },
                    'lower'     => function (string $value) { return strtolower($value); },
                    'upper'     => function (string $value) { return strtoupper($value); },
                    'decimal'   => function ($value) { return $value === null ? null : Decimal::of($value); },
                    'abs'       => function ($value) { return $value === null ? null : Decimal::of($value)->abs(); },
                    'eq'        => function ($value, $other, ?int $precision = null) { return $value === null || $other === null ? false : Decimal::of($value)->eq($other, $precision); },
                    'gt'        => function ($value, $other, ?int $precision = null) { return $value === null || $other === null ? false : Decimal::of($value)->gt($other, $precision); },
                    'gte'       => function ($value, $other, ?int $precision = null) { return $value === null || $other === null ? false : Decimal::of($value)->gte($other, $precision); },
                    'lt'        => function ($value, $other, ?int $precision = null) { return $value === null || $other === null ? false : Decimal::of($value)->lt($other, $precision); },
                    'lte'       => function ($value, $other, ?int $precision = null) { return $value === null || $other === null ? false : Decimal::of($value)->lte($other, $precision); },
                    'add'       => function ($value, $other, ?int $precision = null, ?int $mode = null) { return $value === null || $other === null ? null : Decimal::of($value)->add($other, $precision, $mode); },
                    'sub'       => function ($value, $other, ?int $precision = null, ?int $mode = null) { return $value === null || $other === null ? null : Decimal::of($value)->sub($other, $precision, $mode); },
                    'mul'       => function ($value, $other, ?int $precision = null, ?int $mode = null) { return $value === null || $other === null ? null : Decimal::of($value)->mul($other, $precision, $mode); },
                    'div'       => function ($value, $other, ?int $precision = null, ?int $mode = null) { return $value === null || $other === null ? null : Decimal::of($value)->div($other, $precision, $mode); },
                    'pow'       => function ($value, $other, ?int $precision = null, ?int $mode = null) { return $value === null || $other === null ? null : Decimal::of($value)->pow($other, $precision, $mode); },
                    'sqrt'      => function ($value, ?int $precision = null, ?int $mode = null) { return $value === null ? null : Decimal::of($value)->sqrt($precision, $mode); },
                    'mod'       => function ($value, $modulus) { return $value === null || $modulus === null ? null : Decimal::of($value)->mod($modulus); },
                    'powmod'    => function ($value, $exponent, $modulus) { return $value === null || $exponent === null || $modulus === null ? null : Decimal::of($value)->powmod($exponent, $modulus); },
                    'floor'     => function ($value, int $precision = 0) { return $value === null ? null : Decimal::of($value)->floor($precision); },
                    'round'     => function ($value, int $precision = 0, int $guard_digits = 0, int $precision_type = Decimal::TYPE_DECIMAL_PLACES) { return $value === null ? null : Decimal::of($value)->round($precision, $guard_digits, $precision_type); },
                    'ceil'      => function ($value, int $precision = 0) { return $value === null ? null : Decimal::of($value)->ceil($precision); },
                    'dump'      => function ($value, array $masks = [], string $masked_label = '********') { return Strings::stringify($value, $masks, $masked_label); },
                    'invoke'    => function ($value, ...$args) { return call_user_func($value, ...$args); },
                    'equals'    => function ($value, $other) { return $value == $other; },
                    'sameAs'    => function ($value, $other) { return $value === $other; },
                    'nnvl'      => function ($value, $then, $else = null) { return $value !== null ? $then : $else ; },
                    'nbvl'      => function ($value, $then, $else = null) { return !Utils::isBlank($value) ? $then : $else; },
                    'nevl'      => function ($value, $then, $else = null) { return !Utils::isEmpty($value) ? $then : $else; },
                    'when'      => function ($value, $test, $then, $else = null) {
                        $test = static::peel($test);
                        $test = is_callable($test) ? call_user_func($test, $value) : $test ;
                        return  (is_bool($test) ? $test : $value === $test) ? $then : ($else ?? $value) ;
                    },
                    'case'      => function ($value, array $map, $default = null) { return $map[$value] ?? $default ?? $value; },
                    'length'    => function ($value) {
                        switch (true) {
                            case $value === null:    return null;
                            case is_numeric($value): return mb_strlen((string)$value);
                            case is_string($value):  return mb_strlen($value);
                        }
                        return Arrays::count($value);
                    },
                    'values'    => function (array $value) { return array_values($value); },
                    'keys'      => function (array $value) { return array_keys($value); },
                ],
            ],
        ];
    }

    /**
     * Null value
     *
     * @var self
     */
    private static $null = null;

    /**
     * Original value
     *
     * @var mixed
     */
    protected $origin = null;

    /**
     * Promise of original value for lazy evaluation
     *
     * @var \Closure
     */
    protected $promise = null;

    /**
     * Delegate filters
     *
     * @var array
     */
    protected static $delegate_filters = null;

    /**
     * Safety delegate filters that contains only library layer configure.
     *
     * @var array
     */
    protected static $safety_delegate_filters = null;

    /**
     * Safety mode (only use library filters)
     *
     * @var boolean
     */
    protected $safety = false;

    /**
     * Peel the Tinker wrapper of given value if wrapped
     *
     * @param mixed $value
     * @return mixed of Tinker peeled value
     */
    public static function peel($value)
    {
        return $value instanceof self ? $value->origin() : $value ;
    }

    /**
     * Peel the Tinker wrapper of given all values if wrapped
     *
     * @param array $values
     * @return array of Tinker peeled values
     */
    public static function peelAll(array $values) : array
    {
        return array_map(function ($v) { return static::peel($v); }, $values);
    }

    /**
     * Create a Null Contagion instance
     *
     * @param mixed $origin
     * @param callable|null $promise function():mixed
     * @param boolean $safety (default: false)
     */
    protected function __construct($origin, ?callable $promise = null, bool $safety = false)
    {
        $this->origin  = static::peel($origin) ;
        $this->promise = $promise;
        $this->safety  = $safety;

        if (static::$null === null) {
            static::$null = 'not null';
            static::$null = new static(null);
        }

        static::initDelegateFilters();
    }

    /**
     * Initialize the deligate filters.
     *
     * @return void
     */
    protected static function initDelegateFilters() : void
    {
        if (static::$delegate_filters === null) {
            static::$delegate_filters = [];
            foreach (static::config('filter.delegaters', false, []) as $class => $methods) {
                foreach ($methods as $method_name => $filter_name) {
                    static::$delegate_filters[$filter_name] = "{$class}::".(is_int($method_name) ? $filter_name : $method_name);
                }
            }
        }

        if (static::$safety_delegate_filters === null) {
            static::$safety_delegate_filters = [];
            foreach (Reflector::get(static::defaultConfig(), 'filter.delegaters', []) as $class => $methods) {
                foreach ($methods as $method_name => $filter_name) {
                    static::$safety_delegate_filters[$filter_name] = "{$class}::".(is_int($method_name) ? $filter_name : $method_name);
                }
            }
        }
    }

    /**
     * Add the given filter to Tinker.
     *
     * @param string $name
     * @param callable $filter function(mixed $value, ...$args):mixed
     * @return void
     */
    public static function addFilter(string $name, callable $filter) : void
    {
        static::setConfig(['filter' => ['customs' => [$name => $filter]]]);
    }

    /**
     * Create a value instance
     *
     * @param mixed $origin
     * @param boolean $safety (default: false)
     * @return self
     */
    public static function with($origin, bool $safety = false) : self
    {
        return new static(static::peel($origin), null, $safety) ;
    }

    /**
     * Create a value instance
     *
     * @param \Closure $promise
     * @param boolean $safety (default: false)
     * @return self
     */
    public static function promise(\Closure $promise, bool $safety = false) : self
    {
        return new static(null, $promise, $safety);
    }

    /**
     * Get and initialize the origin value
     *
     * @return mixed
     */
    protected function &origin()
    {
        if ($this->promise !== null && $this->origin === null) {
            $this->origin  = ($this->promise)();
            $this->promise = null;
        }
        return $this->origin;
    }

    /**
     * Return the origin value.
     * If the origin is null then return default.
     * That means the returner's origin argument never becomes null.
     *
     * @param \Closure|null $returner function($origin) { ... } the origin will not be null (default: null)
     * @param mixed $default (default: null)
     * @return mixed
     */
    public function return(?\Closure $returner = null, $default = null)
    {
        $origin = $this->origin();
        if ($origin === null) {
            return $default ;
        }
        return $returner ? $returner($origin) : $origin ;
    }

    /**
     * Property set accessor.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function __set($key, $value)
    {
        $origin = &$this->origin();
        if ($origin === null) {
            return;
        }
        Reflector::set($origin, $key, $value);
    }

    /**
     * Property get accessor.
     *
     * @param string $key
     * @return self|bool
     */
    public function __get($key)
    {
        $origin = $this->origin();
        if ($origin === null) {
            return static::$null;
        }
        $result = Reflector::get($origin, $key);
        return is_bool($result) ? $result : new static($result) ;
    }

    /**
     * Apply the given filter or php function that takes a value to the first argument.
     * Usually you can call any filter as method.
     * If you want to call nl2br then you can
     *
     *   $value->_('escape')->_('nl2br') or $value->escape()->nl2br()
     *
     * @param string $filter
     * @param mixed ...$args
     * @return self|bool
     */
    public function _(string $name, ...$args)
    {
        if ($this->safety) {
            $filter = Reflector::get(static::defaultConfig(), "filter.customs.{$name}") ?? static::$safety_delegate_filters[$name] ?? null ;
        } else {
            $filter = static::config("filter.customs.{$name}", false) ?? static::$delegate_filters[$name] ?? null ;
        }
        $alias  = ltrim(strtolower(preg_replace('/[A-Z]/', '_\0', $name)), '_');
        $filter = $filter ?? (is_callable($alias) ? $alias : null) ?? (is_callable($name) ? $name : null) ;
        return $this->_filter($name, $filter ? \Closure::fromCallable($filter) : null, ...$args);
    }

    /**
     * Apply the filter
     *
     * @param Closure|null $filter
     * @param self ...$args
     * @return self|bool
     */
    protected function _filter(string $name, ?\Closure $filter, ...$args)
    {
        if ($filter === null) {
            return $this;
        }
        $origin    = $this->origin();
        $type      = Reflector::getParameterTypeHintOf($filter, 0);
        $converted = Reflector::convert($origin, $type);
        $args      = array_map(function ($value) { return static::peel($value); }, $args);
        try {
            $result = $filter($converted, ...$args);
            return is_bool($result) ? $result : new static($result);
        } catch (\Throwable $e) {
            if ($origin === null) {
                return static::$null;
            }
            if ($converted === null) {
                throw (new LogicException("Apply {$name} filter failed. The origin type '".Reflector::getType($origin)."' can not convert to {$type}."))->caused($e);
            }
            throw $e;
        }
    }

    /**
     * Method and Filter accessor.
     *
     * If the original method name conflicts with the filter name, the original method takes precedence.
     * At that time, if you want to call the filter with priority, you can execute the filter with the following code.
     *
     *   $value->_('filterName', ...$args)
     *
     * @param string $name
     * @param array $args
     * @return self|bool
     */
    public function __call($name, $args)
    {
        $origin = $this->origin();
        if (is_object($origin) && method_exists($origin, $name)) {
            $method      = new \ReflectionMethod($origin, $name);
            $type        = $method->getReturnType();
            $type        = $type ? $type->getName() : $type ;
            $fingerprint = $type === null || $type == 'bool' || $type == 'boolean' ? md5(serialize($origin)) : null ;
            $args        = array_map(function ($value) { return static::peel($value); }, $args);
            $result      = $method->invoke($origin, ...$args);
            if (
                $type == 'void'
                || (
                    $fingerprint !== null
                    && ($result === null || is_bool($result))
                    && $fingerprint !== md5(serialize($origin))
                )
            ) {
                $result = $origin;
            }
        } else {
            $result = $this->_($name, ...$args);
            $result = static::peel($result);
        }
        return is_bool($result) ? $result : new static($result) ;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value)
    {
        $origin = &$this->origin();
        if (is_array($origin) || is_object($origin)) {
            Reflector::set($origin, $offset, $value);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists($offset)
    {
        return Reflector::has($this->origin(), $offset);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset($offset)
    {
        $origin = $this->origin();
        if (is_array($origin) || is_object($origin)) {
            Reflector::remove($this->origin(), $offset);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        return Arrays::count($this->origin());
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator()
    {
        $origin = $this->origin();
        return new \ArrayIterator(array_map(
            function ($value) {
                return static::with($value);
            },
            is_object($origin) ? get_object_vars($origin) : (array)$origin
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        return Reflector::convert($this->origin(), 'string') ?? '' ;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        return Json::serialize($this->origin());
    }
}
