<?php
namespace Rebet\Database\Pagination\Storage;

use Rebet\Database\Pagination\Cursor;

/**
 * Array Cursor Storage Class.
 *
 * This class for unit testing.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class ArrayCursorStorage implements CursorStorage
{
    /**
     * Strage
     */
    private static $strage = [];

    /**
     * {@inheritDoc}
     */
    public function save(string $name, Cursor $curosr) : void
    {
        static::$strage[$name] = $curosr;
    }

    /**
     * {@inheritDoc}
     */
    public function load(string $name) : ?Cursor
    {
        return static::$strage[$name] ?? null ;
    }

    /**
     * {@inheritDoc}
     */
    public function remove(string $name) : void
    {
        unset(static::$strage[$name]);
    }

    /**
     * {@inheritDoc}
     */
    public function clear() : void
    {
        static::$strage = [];
    }
}
