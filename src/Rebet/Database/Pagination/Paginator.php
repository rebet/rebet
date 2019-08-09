<?php
namespace Rebet\Database\Pagination;

use Rebet\Database\ResultSet;

/**
 * Paginator Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Paginator extends ResultSet
{
    /**
     * Total page count
     *
     * @var long|null
     */
    public $total;

    /**
     * Current page number
     *
     * @var int
     */
    public $page;

    /**
     * Items count per page.
     *
     * @var int
     */
    public $page_size;

    /**
     * Cursor point data
     *
     * @var array|null
     */
    public $cursor;

    /**
     * Last page number
     *
     * @var int
     */
    public $last_page;

    /**
     * Offset position number
     *
     * @var int
     */
    public $offset;

    /**
     * Limit position number
     *
     * @var int
     */
    public $limit;

    /**
     * Create Paginator instance
     *
     * @param mixed $items can be arrayable
     * @param int $page_size
     */
    protected function __construct($items, int $page_size, ?int $page = null, ?long $total = null, ?array $cursor)
    {
        parent::__construct($items);


        $page = (empty($page) || $page < 1) ? 1 : $page ;
        if ($total === null) {
            $last_page = null;
        } else {
            $last_page = floor($total / $page_size) + ($total % $page_size == 0 ? 0 : 1);
            $last_page = $last_page == 0 ? 1 : $last_page ;
            $page      = $last_page < $page ? $last_page : $page ;
        }
        $offset = ($page - 1) * $page_size;
        $limit  = $offset + $page_size - 1;
        $limit  = $total < $limit ? $total - 1 : $limit ;
        $limit  = $limit < 0 ? 0 : $limit ;


        $this->page_size = $page_size;
        $this->page      = $page;
        $this->total     = $total;
    }
}
