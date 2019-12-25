<?php
namespace Rebet\Database\Ransack;

use Rebet\Database\Database;

/**
 * Builtin Ransacker Class
 *
 * This class support `Ransack Search` influenced by activerecord-hackery/ransack for Ruby.
 * Rebet's `Ransack Search` concept is much similar to that of Ruby, but there are differences in predicate keywords and features provided.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class BuiltinRansacker implements Ransacker
{
    /**
     * Database
     *
     * @var Database
     */
    protected $db;

    /**
     * Create ransacker of given databasae.
     *
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * {@inheritDoc}
     */
    public static function of(Database $db) : Ransacker
    {
        return new static($db);
    }

    /**
     * {@inheritDoc}
     */
    public function resolve($ransack_predicate, $value, array $alias = [], ?\Closure $extension = null) : ?array
    {
        return Ransack::resolve($this->db, $ransack_predicate, $value, $alias, $extension);
    }

    /**
     * {@inheritDoc}
     */
    public function build($ransack, array $alias = [], ?\Closure $extension = null) : array
    {
        $wheres = [];
        $params = [];
        foreach ($ransack as $predicate => $value) {
            [$condition, $param] = $this->resolve($predicate, $value, $alias, $extension) ?? [null, null];
            if (!$condition) {
                continue;
            }
            $wheres[] = $condition;
            if ($param !== null) {
                $params = array_merge($params, $param);
            }
        }

        return [implode(' AND ', $wheres), $params];
    }
}
