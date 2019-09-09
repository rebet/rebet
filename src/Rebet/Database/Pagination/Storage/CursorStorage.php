<?php
namespace Rebet\Database\Pagination\Storage;

use Rebet\Database\Pagination\Cursor;

/**
 * Cursor Storage Interface
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
interface CursorStorage
{
    /**
     * Save the cursor as given name to strage.
     *
     * @param string $name
     * @param Cursor $curosor
     * @return string name of cursor
     */
    public function save(string $name, Cursor $curosr) : void;

    /**
     * Load the cursor as given name from strage.
     *
     * @param string $name
     * @return Cursor|null
     */
    public function load(string $name) : ?Cursor;

    /**
     * Remove the cursor as given name from strage.
     *
     * @param string $name
     * @return void
     */
    public function remove(string $name) : void ;

    /**
     * Clear the cursor from strage.
     *
     * @return void
     */
    public function clear() : void ;
}
