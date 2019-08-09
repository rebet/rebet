<?php
namespace Rebet\Database\Pagination;

use Rebet\Common\Reflector;

/**
 * Cursor Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Cursor
{
    private const COLUMN = 0;
    private const ORDER  = 1;
    private const VALUE  = 2;

    /**
     * Cursor pointed data
     *
     * @var array of [[col, asc|desc, $value], ... ]
     */
    protected $cursor = [];

    /**
     * Cursor direction.
     *
     * @var string 'prev' or 'next'
     */
    protected $direction;

    /**
     * Should be include cursor pointed data or not.
     *
     * @var bool (default: true)
     */
    protected $include = true;

    /**
     * Create Cursor instance.
     *
     * @param string $direction 'prev' or 'next'
     * @param array $orders of [[col, asc|desc, $value], ...]
     */
    protected function __construct(string $direction, array $orders)
    {
        $this->direction = $direction;
        $this->cursor    = $orders;
    }

    /**
     * Create cursor pointed given next row data.
     *
     * @param mixed $row
     * @param bool $include (default: true)
     * @return self
     */
    public static function next(array ...$orders) : self
    {
        return new static('next', $orders);
    }

    /**
     * Create cursor pointed given prev row data.
     *
     * @param mixed $row
     * @param bool $include (default: false)
     * @return self
     */
    public static function prev(array ...$orders) : self
    {
        return new static('prev', $orders);
    }

    /**
     * Set include cursor pointed data or not.
     *
     * @param boolean $include (default: true)
     * @return self
     */
    public function include(bool $include = true) : self
    {
        $this->include = $include;
        return $this;
    }

    /**
     * Bind cursor data using given data.
     *
     * @param mixed $data
     * @return self
     */
    public function bind($data) : self
    {
        foreach ($this->cursor as &$cursor) {
            $cursor[Cursor::VALUE] = $cursor[Cursor::VALUE] ?? Reflector::get($data, $cursor[Cursor::COLUMN]);
        }
        return $this;
    }
}
