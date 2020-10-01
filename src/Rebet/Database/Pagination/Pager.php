<?php
namespace Rebet\Database\Pagination;

use Rebet\Tools\Getsetable;
use Rebet\Config\Configurable;

/**
 * Pager Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Pager
{
    use Configurable, Getsetable;

    public static function defaultConfig()
    {
        return [
            'default_page_size'  => 10,
            'default_each_side'  => 0,
            'default_need_total' => false,
            'resolver'           => null,   // function(Pager $pager) : Pager { ... }
        ];
    }

    /**
     * Count of items per page.
     *
     * @var int of page size
     */
    protected $size;

    /**
     * Current page number.
     *
     * @var int of current page
     */
    protected $page;

    /**
     * Each side page count for this pager.
     *
     * @var int
     */
    protected $each_side;

    /**
     * Need total count or not.
     *
     * @var bool
     */
    protected $need_total;

    /**
     * Name of cursor or null.
     * (null for do not use cursor)
     *
     * @var string|null name of cursor
     */
    protected $cursor;

    /**
     * Create Pager instance.
     *
     * @param int $page must be greater equal 1 (default: 1)
     * @param int|null $size must be greater equal 1 (default: depend on configure)
     * @param int|null $each_side must be greater equal 1 (default: depend on configure)
     * @param bool|null $need_total (default: depend on configure)
     * @param string|null $cursor name (default: null for do not use cursor)
     */
    public function __construct(int $page = 1, ?int $size = null, ?int $each_side = null, ?bool $need_total = null, ?string $cursor = null)
    {
        $this->page       = max(1, $page);
        $this->size       = max(1, $size ?? static::config('default_page_size'));
        $this->each_side  = max(0, $each_side ?? static::config('default_each_side'));
        $this->need_total = $need_total ?? static::config('default_need_total');
        $this->cursor     = $cursor;
    }

    /**
     * Create pager using configured resolver.
     *
     * @return Pager
     */
    public static function resolve() : self
    {
        $resolver = static::config('resolver');
        return $resolver(new static());
    }

    /**
     * Get and Set count of items per page.
     *
     * @param int|null $size of page (null for get count of items per page)
     * @var Pager|int
     */
    public function size(?int $size = null)
    {
        return $this->getset('size', $size === null ? null : max(1, $size));
    }

    /**
     * Get and Set current page number
     *
     * @param int|null $page number (null for get current page number)
     * @var Pager|int
     */
    public function page(?int $page = null)
    {
        return $this->getset('page', $page === null ? null : max(1, $page));
    }

    /**
     * Get and Set each side page count for this pager.
     *
     * @param int|null $each_side page count (null for get each side page count)
     * @var Pager|int
     */
    public function eachSide(?int $each_side = null)
    {
        return $this->getset('each_side', $each_side === null ? null : max(0, $each_side));
    }

    /**
     * Get and Set need total count or not.
     *
     * @param bool|null $need_total or not (null for get need total)
     * @var Pager|bool
     */
    public function needTotal(?bool $need_total = null)
    {
        return $this->getset('need_total', $need_total);
    }

    /**
     * Get and Set cursor name
     *
     * @param string|null $name of cursor (null for get cursor name)
     * @var Pager|string|null
     */
    public function cursor(?string $name = null)
    {
        return $this->getset('cursor', $name);
    }

    /**
     * It checks the pager use cursor or not.
     *
     * @return boolean
     */
    public function useCursor() : bool
    {
        return !empty($this->cursor);
    }

    /**
     * Create next page pager
     *
     * @param int $step (default: 1)
     * @return Pager
     */
    public function next(int $step = 1) : self
    {
        $pager = clone $this;
        return $pager->page($pager->page + $step);
    }

    /**
     * Create prev page pager
     *
     * @param int $step (default: 1)
     * @return Pager
     */
    public function prev(int $step = 1) : self
    {
        $pager = clone $this;
        return $pager->page($pager->page - $step);
    }

    /**
     * Check the paging condition was changed between this and given pager or not.
     *
     * @param Pager|null $pager
     * @return boolean
     */
    public function verify(?Pager $pager) : bool
    {
        return
               $pager !== null
            && $this->size === $pager->size
            && $this->each_side === $pager->each_side
            && $this->need_total === $pager->need_total
            && $this->cursor === $pager->cursor
            ;
    }
}
