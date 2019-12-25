<?php
namespace Rebet\Database\Ransack;

use Rebet\Database\Database;

/**
 * Ransacker Interface
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
interface Ransacker
{
    /**
     * Get ransacker of given database.
     *
     * @param Database $db
     * @return self
     */
    public static function of(Database $db) : self;

    /**
     * Resolve 'WHERE' condition expression part from given ransack predicate and value.
     *
     * @param int|string $predicate
     * @param mixed $value
     * @param array $alias (default: [])
     * @param \Closure|null $extention function(Database $db, Ransack $ransack) : ?array (default: null)
     * @return array|null ['where explession', converted value] or null when ignored
     */
    public function resolve($predicate, $value, array $alias = [], ?\Closure $extention = null) : ?array;

    /**
     * Build 'WHERE' condition expression from given ransack conditions.
     *
     * @param mixed $ransack condition that arrayable
     * @param array $alias (default: [])
     * @param \Closure|null $extention function(Database $db, Ransack $ransack) : ?array (default: null)
     * @return array ['where explession', converted values]
     */
    public function build($ransack, array $alias = [], ?\Closure $extention = null) : array;
}
