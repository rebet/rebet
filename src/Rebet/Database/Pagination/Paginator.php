<?php
namespace Rebet\Database\Pagination;

use Rebet\Common\Arrays;
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
    protected $total;

    /**
     * Current page number
     *
     * @var int
     */
    protected $page;

    /**
     * Items count per page.
     *
     * @var int
     */
    protected $page_size;

    /**
     * Next page count that confirmed to be exists
     *
     * @var int|null
     */
    protected $next_page_count;

    /**
     * Last page number
     *
     * @var int|null
     */
    protected $last_page;

    /**
     * from position number
     *
     * @var int
     */
    protected $from;

    /**
     * to position number
     *
     * @var int
     */
    protected $to;

    /**
     * Each side page count of page feed navigator.
     *
     * @var int
     */
    protected $each_side;

    /**
     * Create Paginator instance
     *
     * @param mixed $items can be arrayable
     * @param int $page_size
     */

    /**
     * Create Paginator instance
     * NOTE: Argument total or next_page_count may not be null at least one.
     *
     * @param mixed $items can be arrayable
     * @param int $each_side
     * @param int $page_size
     * @param int|null $page
     * @param int|null $total (default: null)
     * @param int|null $next_page_count (default: null)
     */
    public function __construct($items, int $each_side, int $page_size, ?int $page, ?int $total = null, ?int $next_page_count = null)
    {
        if ($total === null && $next_page_count === null) {
            throw new \InvalidArgumentException("Invalid paginator arguments. Argument total or next_page_count may not be null at least one.");
        }
        parent::__construct($items);

        $count     = Arrays::count($items);
        $page      = (empty($page) || $page < 1) ? 1 : $page ;
        $page_size = $page_size < 1 ? 1 : $page_size ;
        $last_page = null;
        if ($total !== null) {
            $last_page       = intval(floor($total / $page_size) + ($total % $page_size == 0 ? 0 : 1));
            $last_page       = $last_page === 0 ? 1 : $last_page ;
            $page            = $last_page < $page ? $last_page : $page ;
            $next_page_count = $last_page - $page;
        }
        $from = $page === 1 && $count === 0 ? 0 : ($page - 1) * $page_size + 1 ;
        $to   = $from === 0 ? 0 : $from + $count - 1 ;

        $this->each_side       = $each_side;
        $this->total           = $total;
        $this->page            = $page;
        $this->page_size       = $page_size;
        $this->next_page_count = $next_page_count;
        $this->last_page       = $last_page;
        $this->from            = $from;
        $this->to              = $to;
    }

    /**
     * Get the count of current page items.
     *
     * @return int
     */
    public function count() : int
    {
        return Arrays::count($this->items);
    }

    /**
     * Get the total count of all items
     *
     * @return int|null
     */
    public function total() : ?int
    {
        return $this->total;
    }

    /**
     * Get the current page number
     *
     * @return int
     */
    public function page() : int
    {
        return $this->page;
    }

    /**
     * Get the page size (items count per page)
     *
     * @return int
     */
    public function pageSize() : int
    {
        return $this->page_size;
    }

    /**
     * Get the next page count that confirmed to be exists
     *
     * @return int
     */
    public function nextPageCount() : int
    {
        return $this->next_page_count;
    }

    /**
     * Get the last page number.
     *
     * @return integer|null
     */
    public function lastPage() : ?int
    {
        return $this->last_page;
    }

    /**
     * Get the count number that start of current page.
     *
     * @return integer|null
     */
    public function from() : ?int
    {
        return $this->from;
    }

    /**
     * Get the count number that end of current page.
     *
     * @return integer|null
     */
    public function to() : ?int
    {
        return $this->to;
    }

    /**
     * Get the each_side count settings.
     *
     * @return integer|null
     */
    public function eachSide() : int
    {
        return $this->each_side;
    }

    /**
     * It checks next page is exist or not
     *
     * @return bool
     */
    public function hasNext() : bool
    {
        return $this->next_page_count !== 0;
    }

    /**
     * It checks prev page is exist or not
     *
     * @return bool
     */
    public function hasPrev() : bool
    {
        return $this->page > 1;
    }

    /**
     * It checks total page was counted or not
     *
     * @return bool
     */
    public function hasTotal() : bool
    {
        return $this->total !== null;
    }

    /**
     * It checks last page was calculated or not
     *
     * @return bool
     */
    public function hasLastPage() : bool
    {
        return $this->last_page !== null;
    }

    /**
     * Get page numbers on each sides
     *
     * @return array
     */
    public function eachSidePages() : array
    {
        $start = $this->page - $this->each_side;
        $end   = $this->page + $this->each_side;
        if ($start < 1) {
            $end   = min($end - $start + 1, $this->page + $this->next_page_count);
            $start = 1;
        }
        if ($end - $this->page > $this->next_page_count) {
            $end   = $this->page + $this->next_page_count;
            $start = max(1, $start - ($this->each_side - $this->next_page_count));
        }

        $list = [];
        for ($i = $start ; $i <= $end ; $i++) {
            $list[] = $i;
        }

        return $list;
    }
}