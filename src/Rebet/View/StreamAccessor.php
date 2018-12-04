<?php
namespace Rebet\View;

use Rebet\Common\Arrays;
use Rebet\Common\Reflector;
use Rebet\Common\Strings;
use Rebet\Config\Configurable;
use Rebet\DateTime\DateTime;
use Rebet\Common\Json;

/**
 * Stream Accessor Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class StreamAccessor implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable
{
    use Configurable;

    public static function defaultConfig() : array
    {
        return[
            'filters' => [
                'convert'   => function ($value, string $type) { return Reflector::convert($value, $type); },
                'escape'    => function (string $value, string $type = 'html') {
                    switch ($type) {
                        case 'html': return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                        case 'url': return urlencode($value);
                        default: throw new \InvalidArgumentException("Invalid escape type [{$type}] given. The type must be html or url");
                    }
                },
                'nl2br'     => function (string $value) { return nl2br($value); },
                'datetime'  => function (DateTime $value, string $format) { return $value->format($format); },
                'number'    => function (float $value, ...$args) { return number_format($value, ...$args); },
                'text'      => function ($value, string $format) { return $value === null ? null : sprintf($format, $value) ; },
                'default'   => function ($value, $default) { return $value ?? $default; },
                'split'     => function (string $value, string $delimiter, int $limit = PHP_INT_MAX) { return explode($delimiter, $value, $limit); },
                'join'      => function (string $value, string $delimiter) { return implode($delimiter, $value); },
                'replace'   => function (string $value, $pattern, $replacement, int $limit = -1) { return preg_replace($pattern, $replacement, $value, $limit); },
                'cut'       => function (string $value, int $length, string $ellipsis = '...') { return Strings::cut($value, $length, $ellipsis); },
                'lowercase' => function (string $value) { return strtolower($value); },
                'uppercase' => function (string $value) { return strtoupper($value); },
                'dump'      => function (string $value) { return print_r($value, true); },
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
     * Create a Null Contagion instance
     */
    protected function __construct($origin, $promise = null)
    {
        $this->origin  = $origin;
        $this->promise = $promise;
        if (static::$null === null) {
            static::$null = 'not null';
            static::$null = new static(null);
        }
    }

    /**
     * Create a value instance
     *
     * @param mixed $origin
     * @return self
     */
    public static function valueOf($origin) : self
    {
        return $origin instanceof self ? $origin : new static($origin) ;
    }

    /**
     * Create a value instance
     *
     * @param \Closure $promise
     * @return self
     */
    public static function promise(\Closure $promise) : self
    {
        return new static(null, $promise);
    }
    
    /**
     * Get the origin value
     *
     * @return mixed
     */
    public function origin()
    {
        if ($this->promise !== null && $this->origin === null) {
            $this->origin  = ($this->promise)();
            $this->promise = null;
        }
        return $this->origin;
    }

    /**
     * Property accessor.
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
        if ($this->origin() === null) {
            return static::$null;
        }
        $filter = static::config("filters.{$name}", false);
        $filter = $filter ?? (is_callable($name) ? $name : null) ;
        return $this->_filter($filter ? \Closure::fromCallable($filter) : null, ...$args);
    }

    /**
     * Apply the filter
     *
     * @param Closure|null $filter
     * @param self ...$args
     * @return self|bool
     */
    protected function _filter(?\Closure $filter, ...$args)
    {
        if ($filter === null) {
            return $this;
        }
        $function  = new \ReflectionFunction($filter);
        $parameter = $function->getParameters()[0] ?? null;
        $converted = Reflector::convert($this->origin(), Reflector::getTypeHint($parameter));
        try {
            $result = $filter($converted, ...$args);
            return is_bool($result) ? $result : new static($result);
        } catch (\Exception $e) {
            if ($converted === null) {
                return static::$null;
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
        $result = $this->_($name, $args) ?? call_user_func([$origin, $name], ...$args);
        return is_bool($result) ? $result : new static($result) ;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value)
    {
        Reflector::set($this->object, $offset, $value);
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
        Reflector::remove($this->origin(), $offset);
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
        return new \ArrayIterator(is_object($origin) ? get_object_vars($origin) : (array)$origin) ;
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
