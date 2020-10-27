<?php
namespace Rebet\Database\Pagination;

use Rebet\Database\ResultSet;
use Rebet\Tools\Utility\Arrays;
use Rebet\Tools\Utility\Json;

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
     * Start of focus page
     *
     * @var int
     */
    protected $start_of_focus_page;

    /**
     * End of focus page
     *
     * @var int
     */
    protected $end_of_focus_page;

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
     * Pagination link action url.
     *
     * @var string
     */
    protected $action;

    /**
     * Page parameter name of pagination link action.
     *
     * @var string
     */
    protected $page_name;

    /**
     * Pagination link action url anchor.
     *
     * @var string
     */
    protected $anchor;

    /**
     * Pagination link action url queries.
     *
     * @var array
     */
    protected $queries = [];

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

        $start_of_focus_page = $page - $each_side;
        $end_of_focus_page   = $page + $each_side;
        if ($start_of_focus_page < 1) {
            $end_of_focus_page   = min($end_of_focus_page - $start_of_focus_page + 1, $page + $next_page_count);
            $start_of_focus_page = 1;
        }
        if ($end_of_focus_page - $page > $next_page_count) {
            $end_of_focus_page   = $page + $next_page_count;
            $start_of_focus_page = max(1, $start_of_focus_page - ($each_side - $next_page_count));
        }

        $this->each_side           = $each_side;
        $this->total               = $total;
        $this->page                = $page;
        $this->page_size           = $page_size;
        $this->next_page_count     = $next_page_count;
        $this->last_page           = $last_page;
        $this->from                = $from;
        $this->to                  = $to;
        $this->start_of_focus_page = $start_of_focus_page;
        $this->end_of_focus_page   = $end_of_focus_page;
    }

    /**
     * Set the pagination link action url and page parameter name.
     *
     * @param string $action url
     * @param string $page_name (default: 'page')
     * @param string|null $anchor (default: null)
     * @return self
     */
    public function action(string $action, string $page_name = 'page', ?string $anchor = null) : self
    {
        $this->action    = $action;
        $this->page_name = $page_name;
        $this->anchor    = $anchor;
        return $this;
    }

    /**
     * Set/Append the pagination link action url queries.
     * If the null given then reset the queries.
     *
     * @param array|null $queries
     * @return self
     */
    public function with(?array $queries) : self
    {
        $this->queries = $queries === null ? [] : array_merge($this->queries, $queries) ;
        return $this;
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
     * It check the paginator has multi pages.
     *
     * @return boolean
     */
    public function hasPages() : bool
    {
        return $this->page !== 1 || $this->next_page_count !== 0 ;
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
     * Get the previouse page number
     * NOTE: If paginator do not have previouse page then return current page.
     *
     * @return int
     */
    public function prevPage() : int
    {
        return $this->hasPrev() ? $this->page() - 1 : $this->page() ;
    }

    /**
     * Get the next page number
     * NOTE: If paginator do not have next page then return current page.
     *
     * @return int
     */
    public function nextPage() : int
    {
        return $this->hasNext() ? $this->page() + 1 : $this->page() ;
    }

    /**
     * Create page link url of given page.
     * This method do not care the given page is exists or not.
     * NOTE: If the action is not set then return null.
     *
     * @see Paginator::action()
     * @see Paginator::with()
     *
     * @param int $page
     * @param int $encoding PHP_QUERY_* (default: PHP_QUERY_RFC1738)
     * @return string|null
     */
    public function pageUrl(int $page, int $encoding = PHP_QUERY_RFC1738) : ?string
    {
        if (empty($this->action)) {
            return null;
        }
        $anchor = $this->anchor ? "#{$this->anchor}" : '' ;
        return "{$this->action}?". Arrays::toQuery(array_merge($this->queries, [$this->page_name => $page]), $encoding).$anchor;
    }

    /**
     * Create first page link url.
     * NOTE: If the action is not set then return null.
     *
     * @param int $encoding PHP_QUERY_* (default: PHP_QUERY_RFC1738)
     * @return string|null
     */
    public function firstPageUrl(int $encoding = PHP_QUERY_RFC1738) : ?string
    {
        return $this->pageUrl(1, $encoding) ;
    }

    /**
     * Create previous page link url.
     * NOTE:
     *   - If the action is not set then return null.
     *   - If the paginator do not have previous page then return null.
     *
     * @param int $encoding PHP_QUERY_* (default: PHP_QUERY_RFC1738)
     * @return string|null
     */
    public function prevPageUrl(int $encoding = PHP_QUERY_RFC1738) : ?string
    {
        return $this->hasPrev() ? $this->pageUrl($this->page - 1, $encoding) : null ;
    }

    /**
     * Create next page link url.
     * NOTE:
     *   - If the action is not set then return null.
     *   - If the paginator do not have next page then return null.
     *
     * @param int $encoding PHP_QUERY_* (default: PHP_QUERY_RFC1738)
     * @return string|null
     */
    public function nextPageUrl(int $encoding = PHP_QUERY_RFC1738) : ?string
    {
        return $this->hasNext() ? $this->pageUrl($this->page + 1, $encoding) : null ;
    }

    /**
     * Create last page link url.
     * NOTE:
     *   - If the action is not set then return null.
     *   - If the paginator do not have last page then return null.
     *
     * @param int $encoding PHP_QUERY_* (default: PHP_QUERY_RFC1738)
     * @return string|null
     */
    public function lastPageUrl(int $encoding = PHP_QUERY_RFC1738) : ?string
    {
        return $this->hasLastPage() ? $this->pageUrl($this->last_page, $encoding) : null ;
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
     * Determine if the paginator is on the first page.
     *
     * @return bool
     */
    public function onFirstPage() : bool
    {
        return $this->page === 1;
    }

    /**
     * Determine if the paginator is on the last page.
     *
     * @return bool
     */
    public function onLastPage() : bool
    {
        return !$this->hasNext();
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
     * Get start focused page number near the current page that considered the range of each sides.
     *
     * @return int
     */
    public function startOfFocusPage() : int
    {
        return $this->start_of_focus_page;
    }

    /**
     * Get end focused page number near the current page that considered the range of each sides.
     *
     * @return int
     */
    public function endOfFocusPage() : int
    {
        return $this->end_of_focus_page;
    }

    /**
     * Get focused page numbers near the current page that considered the range of each sides.
     *
     * @return array
     */
    public function focusPages() : array
    {
        $list = [];
        for ($i = $this->start_of_focus_page ; $i <= $this->end_of_focus_page ; $i++) {
            $list[] = $i;
        }
        return $list;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        $page_urls = [];
        foreach ($this->focusPages() as $page) {
            $page_urls[$page] = $this->pageUrl($page);
        }
        return Json::serialize([
            'has_pages'           => $this->hasPages(),
            'has_total'           => $this->hasTotal(),
            'has_prev'            => $this->hasPrev(),
            'has_next'            => $this->hasNext(),
            'has_last_page'       => $this->hasLastPage(),
            'on_first_page'       => $this->onFirstPage(),
            'on_last_page'        => $this->onLastPage(),
            'total'               => $this->total(),
            'page_size'           => $this->pageSize(),
            'page'                => $this->page(),
            'prev_page'           => $this->prevPage(),
            'next_page'           => $this->nextPage(),
            'last_page'           => $this->lastPage(),
            'first_page_url'      => $this->firstPageUrl(),
            'prev_page_url'       => $this->prevPageUrl(),
            'next_page_url'       => $this->nextPageUrl(),
            'last_page_url'       => $this->lastPageUrl(),
            'page_urls'           => $page_urls,
            'each_side'           => $this->eachSide(),
            'from'                => $this->from(),
            'to'                  => $this->to(),
            'start_of_focus_page' => $this->startOfFocusPage(),
            'end_of_focus_page'   => $this->endOfFocusPage(),
            'data'                => $this->toArray(),
        ]);
    }
}
