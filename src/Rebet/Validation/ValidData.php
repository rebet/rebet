<?php
declare(strict_types=1);

namespace Rebet\Validation;

use Rebet\Tools\Reflection\Describable;
use Rebet\Tools\Reflection\Reflector;
use Rebet\Tools\Support\Arrayable;
use Rebet\Tools\Utility\Arrays;

/**
 * Valid Data Class
 *
 * @implements \ArrayAccess<int|string, mixed>
 * @implements \IteratorAggregate<int|string, mixed>
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
#[\AllowDynamicProperties]
class ValidData implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable
{
    use Arrayable, Describable;

    /**
     * Valid data.
     *
     * @var array<int|string, mixed>
     */
    protected $data = null;

    /**
     * Create Valid Data
     *
     * @param mixed $data
     */
    public function __construct($data = [])
    {
        $this->data = Arrays::toArray($data);
    }

    /**
     * {@inheritDoc}
     *
     * @return array<int|string, mixed>
     */
    protected function &container() : array
    {
        return $this->data;
    }

    /**
     * Property accessor.
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->container()[$key] ?? null ;
    }

    /**
     * Get the value of given key using dot notation.
     *
     * @param string $key of dot notation
     * @param mixed $default (default: null)
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return Reflector::get($this->container(), $key, $default);
    }
}
