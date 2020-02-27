<?php
namespace Rebet\Stream;

use Rebet\Common\Arrays;
use Rebet\Common\Decimal;
use Rebet\Common\Exception\LogicException;
use Rebet\Common\Json;
use Rebet\Common\Reflector;
use Rebet\Common\Strings;
use Rebet\Common\Utils;
use Rebet\Config\Configurable;
use Rebet\DateTime\DateTime;
use Rebet\Inflection\Inflector;

/**
 * Stream Class
 *
 * @method Stream convert(?string $type)
 * @method bool   isBlank()
 * @method Stream bvl($default)
 * @method bool   isEmpty()
 * @method Stream evl($default)
 * @method Stream lcut(int $length, string $encoding = 'UTF-8')
 * @method Stream rcut(int $length, string $encoding = 'UTF-8')
 * @method Stream clip(int $length, string $ellipsis = '...')
 * @method Stream indent(string $char = "\t", int $depth = 1)
 * @method Stream ltrim(string $prefix = ' ', ?int $max = null)
 * @method Stream rtrim(string $suffix = ' ', ?int $max = null)
 * @method Stream trim(string $deletion = ' ', ?int $max = null)
 * @method Stream mbtrim()
 * @method bool   startsWith(string $needle)
 * @method bool   endsWith(string $needle)
 * @method bool   contains(string|string[] $searches, ?int $at_least = null)
 * @method bool   match(string|string[] $patterns)
 * @method bool   wildmatch(string|string[] $patterns)
 * @method Stream split(string $delimiter, int $size, $padding = null)
 * @method Stream pluck(int|string|\Closure|null $value_field, int|string|\Closure|null $key_field = null) - Closure : `function($index, $key, $value) {...}`
 * @method Stream override($diff, array|string $option = [], string $default_array_override_option = OverrideOption::APEND, ?\Closure $handler = null) - Closure : `function($index, $key, $value) {...}`
 * @method Stream duplicate()
 * @method Stream crossJoin(iterable ...$arrays)
 * @method Stream only(array|string $keys)
 * @method Stream except(array|string $keys)
 * @method Stream where(?callable $callback) - $callback : `function($value[, $key]) : bool {...}`
 * @method Stream compact()
 * @method Stream first(?callable $callback = null, $default = null) - $callback : `function($value[, $key]) : bool {...}`
 * @method Stream last(?callable $callback = null, $default = null) - $callback : `function($value[, $key]) : bool {...}`
 * @method Stream flatten(int $depth = INF)
 * @method Stream prepend($value, $key = null)
 * @method Stream shuffle(?int $seed = null)
 * @method Stream map(callable $callback) - $callback : `function($value[, $key]) {...}`
 * @method Stream reduce(callable $reducer, $initial = null) - $reducer : `function($carry, $item) {...}`
 * @method Stream diff($items, ?callable $comparator = null) - $comparator : `function($a, $b) : int {...}`
 * @method Stream intersect($items, ?callable $comparator = null) - $comparator : `function($a, $b) : int {...}`
 * @method Stream every(callable $test) - $test : `function($v, $k) : bool {...}`
 * @method Stream groupBy(callable|string|array $group_by = null, bool $preserve_keys = false) - $group_by : `function($value, $key) {...}`
 * @method Stream union($other)
 * @method Stream min(callable|string|null $retriever = null, $initial = null) - $retriever : `function($value) {...}`
 * @method Stream max(callable|string|null $retriever = null, $initial = null) - $retriever : `function($value) {...}`
 * @method Stream sort(int $order = SORT_ASC, callable|int $comparator = SORT_REGULAR) - $comparator :  `function($a, $b) : int`
 * @method Stream sortBy(callable|string $retriever, int $order = SORT_ASC, callable|int $comparator = SORT_REGULAR) - $retriever : `function($value) {...}` $comparator :  `function($a, $b) : int {...}`
 * @method Stream sortKeys(int $order = SORT_ASC, callable|int $comparator = SORT_REGULAR) - $comparator :  `function($a, $b) : int`
 * @method Stream sum(callable|string|null $retriever = null, bool $arbitrary_precision = false, ?int $precision = null) - $retriever : `function($value) {...}`
 * @method Stream avg(callable|string|null $retriever = null, bool $arbitrary_precision = false, ?int $precision = null) - $retriever : `function($value) {...}`
 * @method Stream median(callable|string|null $retriever = null, bool $arbitrary_precision = false, ?int $precision = null) - $retriever : `function($value) {...}`
 * @method Stream mode(callable|string|null $retriever = null) - $retriever : `function($value) {...}`
 * @method Stream implode(string $delimiter = ', ')
 * @method bool   isNull()
 * @method Stream nvl($default)
 * @method Stream default($default)
 * @method Stream escape(string $type = 'html') - $type : `'html'|'url'`
 * @method Stream nl2br()
 * @method Stream datetime(string $format)
 * @method Stream text(string $format)
 * @method Stream explode(string $delimiter, int $limit = PHP_INT_MAX)
 * @method Stream replace($pattern, $replacement, int $limit = -1)
 * @method Stream lower()
 * @method Stream upper()
 * @method Stream decimal()
 * @method Stream floor(int $precision = 0)
 * @method Stream round(int $precision = 0, int $guard_digits = 0, int $precision_type = Decimal::TYPE_DECIMAL_PLACES)
 * @method Stream ceil(int $precision = 0)
 * @method Stream number(int $precision = 0, bool $omit_zero = false, string $decimal_point = '.', string $thousands_separator = ',')
 * @method Stream dump()
 * @method Stream invoke(...$args)
 * @method bool   equals($other)
 * @method bool   sameAs($other)
 * @method Stream nnvl($then, $else = null)
 * @method Stream nbvl($then, $else = null)
 * @method Stream nevl($then, $else = null)
 * @method Stream when(mixed $test, $then, $else = null) - $test : `function($value) {...}`
 * @method Stream case(array $map, $default = null)
 * @method Stream length()
 * @method Stream values()
 * @method Stream keys()
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Stream implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable
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
                        'pluck', 'override', 'duplicate', 'crossJoin', 'only', 'except', 'where', 'compact',
                        'first', 'last', 'flatten', 'prepend', 'shuffle', 'map', 'reduce', 'diff', 'intersect',
                        'every', 'groupBy', 'union', 'min', 'max', 'sort', 'sortBy', 'sortKeys', 'sum', 'avg',
                        'median', 'mode', 'implode', 'toQuery'
                    ],
                ],
                'customs' => [
                    // You can use php built-in functions as filters when the 1st argument is for value.
                    'isNull'   => function ($value) { return $value === null; },
                    'nvl'      => function ($value, $default) { return $value ?? $default; },
                    'default'  => function ($value, $default) { return $value ?? $default; },
                    'escape'   => function (string $value, string $type = 'html') {
                        switch ($type) {
                            case 'html': return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                            case 'url': return urlencode($value);
                            default: throw new \InvalidArgumentException("Invalid escape type [{$type}] given. The type must be html or url");
                        }
                    },
                    'nl2br'    => function (string $value) { return nl2br($value); },
                    'datetime' => function (DateTime $value, string $format) { return $value->format($format); },
                    'text'     => function ($value, string $format) { return $value === null ? null : sprintf($format, $value) ; },
                    'explode'  => function (string $value, string $delimiter, int $limit = PHP_INT_MAX) { return explode($delimiter, $value, $limit); },
                    'replace'  => function (string $value, $pattern, $replacement, int $limit = -1) { return preg_replace($pattern, $replacement, $value, $limit); },
                    'lower'    => function (string $value) { return strtolower($value); },
                    'upper'    => function (string $value) { return strtoupper($value); },
                    'decimal'  => function ($value) { return $value === null ? null : Decimal::of($value); },
                    'floor'    => function ($value, int $precision = 0) { return $value === null ? null : Decimal::of($value)->floor($precision); },
                    'round'    => function ($value, int $precision = 0, int $guard_digits = 0, int $precision_type = Decimal::TYPE_DECIMAL_PLACES) { return $value === null ? null : Decimal::of($value)->round($precision, $guard_digits, $precision_type); },
                    'ceil'     => function ($value, int $precision = 0) { return $value === null ? null : Decimal::of($value)->ceil($precision); },
                    'number'   => function ($value, int $precision = 0, bool $omit_zero = false, string $decimal_point = '.', string $thousands_separator = ',') { return $value === null ? null : Decimal::of($value)->round($precision)->format($omit_zero, $decimal_point, $thousands_separator); },
                    'dump'     => function ($value) { return print_r($value, true); },
                    'invoke'   => function ($value, ...$args) { return call_user_func($value, ...$args); },
                    'equals'   => function ($value, $other) { return $value == $other; },
                    'sameAs'   => function ($value, $other) { return $value === $other; },
                    'nnvl'     => function ($value, $then, $else = null) { return $value !== null ? $then : $else ; },
                    'nbvl'     => function ($value, $then, $else = null) { return !Utils::isBlank($value) ? $then : $else ; },
                    'nevl'     => function ($value, $then, $else = null) { return !Utils::isEmpty($value) ? $then : $else ; },
                    'when'     => function ($value, $test, $then, $else = null) {
                        $test = static::peel($test);
                        $test = is_callable($test) ? call_user_func($test, $value) : $test ;
                        return  (is_bool($test) ? $test : $value === $test) ? $then : ($else ?? $value) ;
                    },
                    'case'     => function ($value, array $map, $default = null) { return $map[$value] ?? $default ?? $value; },
                    'length'   => function ($value) {
                        switch (true) {
                            case $value === null:    return null;
                            case is_numeric($value): return mb_strlen((string)$value);
                            case is_string($value):  return mb_strlen($value);
                        }
                        return Arrays::count($value);
                    },
                    'values'   => function (array $value) { return array_values($value); },
                    'keys'     => function (array $value) { return array_keys($value); },
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
     * Peel the stream wrapper of given value if wrapped
     *
     * @param mixed $value
     * @return void
     */
    protected static function peel($value)
    {
        return $value instanceof self ? $value->origin() : $value ;
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
     * Add the given filter to Stream.
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
    public static function of($origin, bool $safety = false) : self
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
        $alias  = Inflector::snakize($name);
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
        $type      = Reflector::getTypeHintOf($filter, 0);
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
                throw LogicException::by("Apply {$name} filter failed. The origin type '". Reflector::getType($origin) ."' can not convert to {$type}.")->caused($e);
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
                return static::of($value);
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
