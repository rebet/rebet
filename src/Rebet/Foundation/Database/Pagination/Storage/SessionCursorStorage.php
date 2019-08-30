<?php
namespace Rebet\Foundation\Database\Pagination\Storage;

use Rebet\Database\Pagination\Cursor;
use Rebet\Database\Pagination\Storage\CursorStorage;
use Rebet\Http\Session\Session;

/**
 * Session Cursor Storage Class.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class SessionCursorStorage implements CursorStorage
{
    /**
     * {@inheritDoc}
     */
    public function save(string $name, Cursor $curosr) : void
    {
        Session::current()->set($name, $curosr);
    }

    /**
     * {@inheritDoc}
     */
    public function load(string $name) : ?Cursor
    {
        return Session::current()->get($name);
    }

    /**
     * {@inheritDoc}
     */
    public function remove(string $name) : void
    {
        Session::current()->remove($name);
    }
}
