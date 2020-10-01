<?php
namespace Rebet\Database;

use Rebet\Tools\Support\Arrayable;

/**
 * Order By Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class OrderBy implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable
{
    use Arrayable;

    /**
     * Orders
     *
     * @var array of [col => asc|desc, ... ]
     */
    protected $order_by = [];

    /**
     * Create Cursor instance.
     *
     * @param array $order_by of [col => asc|desc, ... ]
     */
    public function __construct(array $order_by)
    {
        $this->order_by = array_map(function ($v) { return strtoupper($v); }, $order_by);
    }

    /**
     * {@inheritDoc}
     */
    protected function &container() : array
    {
        return $this->order_by;
    }

    /**
     * Get the reverse order
     *
     * @return self
     */
    public function reverse() : self
    {
        return new static(array_map(function ($v) { return $v === 'ASC' ? 'DESC' : 'ASC'; }, $this->order_by));
    }

    /**
     * Create order by instance from given value.
     *
     * @param mixed $order_by
     * @return self|null
     */
    public static function valueOf($order_by) : ?self
    {
        switch (true) {
            case empty($order_by):            return null;
            case $order_by instanceof static: return $order_by;
            case is_array($order_by):         return new static($order_by);
        }
        return null;
    }
}
