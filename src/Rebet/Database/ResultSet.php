<?php
namespace Rebet\Database;

use Rebet\Common\Arrayable;
use Rebet\Common\Arrays;

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
    }

    /**
     * {@inheritDoc}
     */
    protected function &container() : array
    {
        return $this->items;
    }
}
