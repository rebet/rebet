<?php
namespace Rebet\Database\Pagination;

use Rebet\Tools\Config\Configurable;
use Rebet\Tools\Support\Getsetable;

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

    /**
     * {@inheritDoc}
     * @see https://github.com/rebet/rebet/blob/master/src/Rebet/Application/Console/Command/skeltons/configs/database.letterpress.php
     */
    public static function defaultConfig()
    {
        return [
            'default_page_size'  => 10,
            'max_page_size'      => 100,
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
     */
    public function __construct()
    {
        $this->page(1)
             ->size(static::config('default_page_size'), true)
             ->eachSide(static::config('default_each_side'))
             ->needTotal(static::config('default_need_total'))
             ;
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
     * @param bool $limit_exceedable for max page size (default: false)
     * @return Pager|int
     */
    public function size(?int $size = null, bool $limit_exceedable = false)
    {
        return $this->getset('size', $size === null ? null : min(max(1, $size), $limit_exceedable ? PHP_INT_MAX : static::config('max_page_size')));
    }

    /**
     * Get and Set current page number
     *
     * @param int|null $page number (null for get current page number)
     * @return Pager|int
     */
    public function page(?int $page = null)
    {
        return $this->getset('page', $page === null ? null : max(1, $page));
    }

    /**
     * Get and Set each side page count for this pager.
     *
     * @param int|null $each_side page count (null for get each side page count)
     * @return Pager|int
     */
    public function eachSide(?int $each_side = null)
    {
        return $this->getset('each_side', $each_side === null ? null : max(0, $each_side));
    }

    /**
     * Get and Set need total count or not.
     *
     * @param bool|null $need_total or not (null for get need total)
     * @return Pager|bool
     */
    public function needTotal(?bool $need_total = null)
    {
        return $this->getset('need_total', $need_total);
    }

    /**
     * Get and Set cursor name
     *
     * @param string|null $name of cursor (null for get cursor name)
     * @return Pager|string|null
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
