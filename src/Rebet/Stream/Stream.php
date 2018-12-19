<?php
namespace Rebet\Stream;

use Rebet\Common\Arrays;
use Rebet\Common\Json;
use Rebet\Common\Math;
use Rebet\Common\Reflector;
use Rebet\Common\Strings;
use Rebet\Common\Utils;
use Rebet\Config\Configurable;
use Rebet\DateTime\DateTime;
use Rebet\Inflection\Inflector;

/**
 * Stream Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Stream implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable
{
    use Configurable;

    public static function defaultConfig() : array
    {
        return[
            'filter' => [
                'delegaters' => [
                    Reflector::class => ['convert'],
                    Math::class      => ['floor', 'round', 'ceil', 'format' => 'number'],
                    Utils::class     => ['isBlank', 'bvl', 'isEmpty', 'evl'],
                    Strings::class   => ['cut', 'indent', 'ltrim', 'rtrim', 'mbtrim' => 'trim', 'startsWith', 'endsWith', 'contains', 'match', 'wildmatch'],
                    Arrays::class    => ['pluck', 'override', 'duplicate', 'crossJoin', 'only', 'except', 'where', 'compact', 'first', 'last', 'flatten', 'prepend', 'shuffle', 'map'],
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
                    'nl2br'    => function (string $value) { return nl2br($value); },
                    'datetime' => function (DateTime $value, string $format) { return $value->format($format); },
                    'text'     => function ($value, string $format) { return $value === null ? null : sprintf($format, $value) ; },
                    'explode'  => function (string $value, string $delimiter, int $limit = PHP_INT_MAX) { return explode($delimiter, $value, $limit); },
                    'implode'  => function (array $value, string $delimiter) { return implode($delimiter, $value); },
                    'replace'  => function (string $value, $pattern, $replacement, int $limit = -1) { return preg_replace($pattern, $replacement, $value, $limit); },
                    'lower'    => function (string $value) { return strtolower($value); },
                    'upper'    => function (string $value) { return strtoupper($value); },
                    'dump'     => function ($value) { return print_r($value, true); },
                    'invoke'   => function ($value, ...$args) { return call_user_func($value, ...$args); },
                    'equals'   => function ($value, $other) { return $value == $other; },
                    'sameAs'   => function ($value, $other) { return $value === $other; },
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
     * @param callable|null $promise
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
     * @param callable $filter
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
        $function  = new \ReflectionFunction($filter);
        $parameter = $function->getParameters()[0] ?? null;
        $type      = Reflector::getTypeHint($parameter);
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
                throw new \LogicException("Apply {$name} filter failed. The origin value '{$origin}' can not convert to {$type}.", 0, $e);
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
