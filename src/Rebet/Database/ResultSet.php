<?php
namespace Rebet\Database;

use Rebet\Database\DataModel\DataModel;
use Rebet\Tools\Support\Arrayable;
use Rebet\Tools\Utility\Arrays;

/**
 * Result Set Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class ResultSet implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable
{
    use Arrayable;

    /**
     * All of the items in result set.
     *
     * @var array
     */
    protected $items;

    /**
     * Create result set instance
     *
     * @param mixed $items can be arrayable (default: [])
     */
    public function __construct($items = [])
    {
        $this->items = Arrays::toArray($items);
        foreach ($this->items as $item) {
            if ($item instanceof DataModel) {
                $item->belongsResultSet($this);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function &container() : array
    {
        return $this->items;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value)
    {
        if ($value instanceof DataModel) {
            $value->belongsResultSet($this);
        }
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    /**
     * Reverse the items order.
     *
     * @return self
     */
    public function reverse() : self
    {
        $this->items = array_reverse($this->items);
        return $this;
    }

    /**
     * Pluck an array of values from a result set items using Arrays::pluck().
     *
     * @param int|string|\Closure|null $value_field Field name / index / extract function as the value of extracted data (Row element itself is targeted when blank is specified)
     * @param int|string|\Closure|null $key_field Field name / index / extract function as key of extracted data (It becomes serial number array when blank is specified)
     * @return array
     */
    public function pluk($value_field, $key_field = null) : array
    {
        return Arrays::pluck($this->items, $value_field, $key_field);
    }
}
