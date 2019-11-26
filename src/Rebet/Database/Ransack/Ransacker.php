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
     * Build 'WHERE' condition expression part from given ransack predicate and value.
     *
     * @param int|string $predicate
     * @param mixed $value
     * @param array $alias (default: [])
     * @param \Closure|null $extention function(Database $db, Ransack $ransack) : ?array (default: null)
     * @return array|null ['where explession', converted value] or null when ignored
     */
    public function convert($predicate, $value, array $alias = [], ?\Closure $extention = null) : ?array;
}
