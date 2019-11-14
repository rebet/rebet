<?php
namespace Rebet\Database;

use Rebet\Common\Arrayable;
use Rebet\Common\Arrays;
use Rebet\Database\DataModel\DataModel;

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
     * @param mixed $items can be arrayable
     */
    public function __construct($items)
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
        $brs = &$this->belongsResultSet();
        if ($offset === null) {
            $brs[] = $value;
        } else {
            $brs[$offset] = $value;
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
}
