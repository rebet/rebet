<?php

declare(strict_types=1);

namespace Rebet\Database\Pagination\Storage;

use Override;
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
     *
     * @var array<string, Cursor>
     */
    private static $strage = [];

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function save(string $name, Cursor $cursor): void
    {
        self::$strage[$name] = $cursor;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function load(string $name): Cursor|null
    {
        return self::$strage[$name] ?? null ;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function remove(string $name): void
    {
        unset(self::$strage[$name]);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function clear(): void
    {
        self::$strage = [];
    }
}
