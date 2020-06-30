<?php
namespace Rebet\Database\Pagination;

use Rebet\Common\Arrayable;
use Rebet\Common\Reflector;
use Rebet\Common\Unit;
use Rebet\Common\Utils;
use Rebet\Config\Configurable;
use Rebet\Database\Pagination\Storage\CursorStorage;
use Rebet\DateTime\DateTime;

/**
 * Cursor Class
 *
 * The cursor always points first item of given page.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Cursor implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable
{
    use Configurable, Arrayable;

    public static function defaultConfig()
    {
        return [
            'storage'  => null,
            'lifetime' => '1h',
        ];
    }

    /**
     * Cursor pointed data
     *
     * @var array of [col => $value, ... ]
     */
    protected $cursor = [];

    /**
     * Pager of this cursor
     *
     * @var Pager
     */
    protected $pager;

    /**
     * Next page count that confirmed to be exists
     *
     * @var int|null
     */
    protected $next_page_count;

    /**
     * Cursor created at
     *
     * @var DateTime
     */
    protected $created_at;

    /**
     * Create Cursor instance.
     *
     * @param Pager $pager
     * @param array $cursor of [col => $value, ... ]
     * @param int|null $next_page_count that confirmed to be exists
     */
    public function __construct(Pager $pager, array $cursor, ?int $next_page_count = null)
    {
        $this->pager           = $pager;
        $this->cursor          = $cursor;
        $this->next_page_count = $next_page_count;
        $this->created_at      = DateTime::now();
    }

    /**
     * {@inheritDoc}
     */
    protected function &container() : array
    {
        return $this->cursor;
    }

    /**
     * Create given pages cursor using given column orders and cursor pointed data.
     *
     * @param OrderBy|array $order_by
     * @param Pager $pager
     * @param object|array $data of cursor poitned
     * @param int $next_page_count that confirmed to be exists
     * @return self
     */
    public static function create($order_by, Pager $pager, $data, ?int $next_page_count) : self
    {
        $cursor = [];
        foreach ($order_by as $col => $order) {
            $cursor[$col] = Reflector::get($data, $col);
        }
        return new static($pager, $cursor, $next_page_count);
    }

    /**
     * It checks this cursor was expired or not.
     *
     * @return bool
     */
    public function expired() : bool
    {
        $lifetime = Unit::of(Unit::TIME)->parse(static::config('lifetime', false, 0), 'ms')->toInt();
        if ($lifetime === 0) {
            return false;
        }
        return $this->created_at->addMilli($lifetime) < DateTime::now() ;
    }

    /**
     * Get the pager of cursor.
     *
     * @return Pager
     */
    public function pager() : Pager
    {
        return $this->pager;
    }

    /**
     * Get next page count that confirmed to be exists.
     *
     * @return int|null
     */
    public function nextPageCount() : ?int
    {
        return $this->next_page_count;
    }

    /**
     * Save the cursor to storage.
     * NOTE: Name of cursor will use pager->curosr setting.
     *       If the pager->cursor is empty then this method do nothing.
     *
     * @param CursorStorage|null $strage (default: depend on configured)
     * @return self
     */
    public function save(?CursorStorage $strage = null) : self
    {
        if (!$this->pager->useCursor()) {
            return $this;
        }
        $strage = $strage ?? static::configInstantiate('storage') ;
        $strage->save($this->pager->cursor(), $this);
        return $this;
    }

    /**
     * Load the cursor from strage.
     *
     * @param string $name of cursor
     * @param CursorStorage|null $strage (default: depend on configured)
     * @return self|null
     */
    public static function load(string $name, ?CursorStorage $strage = null) : ?self
    {
        $strage = $strage ?? static::configInstantiate('storage') ;
        $cursor = $strage->load($name);
        return $cursor === null || $cursor->expired() ? null : $cursor ;
    }

    /**
     * Remove the cursor from strage.
     *
     * @param string $name of cursor
     * @param CursorStorage|null $strage (default: depend on configured)
     */
    public static function remove(string $name, ?CursorStorage $strage = null) : void
    {
        $strage = $strage ?? static::configInstantiate('storage') ;
        $strage->remove($name);
    }

    /**
     * Clear the cursor from strage.
     *
     * @param CursorStorage|null $strage (default: depend on configured)
     */
    public static function clear(?CursorStorage $strage = null) : void
    {
        $strage = $strage ?? static::configInstantiate('storage') ;
        $strage->clear();
    }

    /**
     * It checks the cursor equals given other cursor.
     * This method ignore created_at timestamp for expired check.
     *
     * @param Cursor|null $cursor
     * @return boolean
     */
    public function equals(?Cursor $cursor) : bool
    {
        return $cursor !== null
            && Utils::equivalent($this->cursor, $cursor->cursor)
            && $this->pager == $cursor->pager
            && $this->next_page_count == $cursor->next_page_count
            ;
    }
}
