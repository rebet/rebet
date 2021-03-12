<?php
namespace Rebet\Database\Ransack;

use Rebet\Database\Database;
use Rebet\Database\Driver\Driver;
use Rebet\Database\Query;

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
     * PDO Driver
     *
     * @var Driver
     */
    protected $driver;

    /**
     * Create ransacker of given PDO driver.
     *
     * @param Database $db
     */
    public function __construct(Driver $driver)
    {
        $this->driver = $driver;
    }

    /**
     * {@inheritDoc}
     */
    public static function of(Driver $driver) : Ransacker
    {
        return new static($driver);
    }

    /**
     * {@inheritDoc}
     */
    public function resolve($ransack_predicate, $value, array $alias = [], ?\Closure $extension = null) : ?Query
    {
        return Ransack::resolve($this->driver, $ransack_predicate, $value, $alias, $extension);
    }

    /**
     * {@inheritDoc}
     */
    public function build($ransack, array $alias = [], ?\Closure $extension = null) : Query
    {
        $wheres = [];
        $params = [];
        foreach ($ransack as $predicate => $value) {
            $condition = $this->resolve($predicate, $value, $alias, $extension) ?? null;
            if (!$condition) {
                continue;
            }
            $wheres[] = $condition->sql();
            $params   = array_merge($params, $condition->params());
        }

        return $this->driver->sql(implode(' AND ', $wheres), $params);
    }
}
