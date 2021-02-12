<?php
namespace Rebet\Database\Ransack;

use Rebet\Database\Driver\Driver;
use Rebet\Database\Query;

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
     * Get ransacker of given PDO driver.
     *
     * @param Driver $driver
     * @return self
     */
    public static function of(Driver $driver) : self;

    /**
     * Resolve 'WHERE' condition expression part from given ransack predicate and value.
     *
     * @param int|string $predicate
     * @param mixed $value
     * @param array $alias (default: [])
     * @param \Closure|null $extention function(Ransack $ransack) : ?Condition (default: null)
     * @return Query|null condition or null when ignored
     */
    public function resolve($predicate, $value, array $alias = [], ?\Closure $extention = null) : ?Query;

    /**
     * Build 'WHERE' condition expression from given ransack conditions.
     *
     * @param mixed $ransack condition that arrayable
     * @param array $alias (default: [])
     * @param \Closure|null $extention function(Ransack $ransack) : ?Condition (default: null)
     * @return Query
     */
    public function build($ransack, array $alias = [], ?\Closure $extention = null) : Query;
}
