<?php
namespace Rebet\Database;

use Rebet\Annotation\AnnotatedClass;
use Rebet\Common\Reflector;
use Rebet\Database\Annotation\PhpType;
use Rebet\Database\DataModel\Entity;
use Rebet\Database\Exception\DatabaseException;

/**
 * Statement Class
 *
 * @todo support fetched data cache and repeatble method call (if need)
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Statement implements \IteratorAggregate
{
    /**
     * Database of this statement.
     *
     * @var Database
     */
    protected $db;

    /**
     * PDO statement instance.
     *
     * @var \PDOStatement
     */
    protected $stmt;

    /**
     * Create statement instance.
     *
     * @param Database $db
     * @param PDOStatement $stmt
     */
    public function __construct(Database $db, \PDOStatement $stmt)
    {
        $this->db   = $db;
        $this->stmt = $stmt;
    }

    /**
     * Get original PDO statement instance.
     *
     * @return \PDOStatement
     */
    public function raw() : \PDOStatement
    {
        return $this->stmt;
    }

    /**
     * Get the column meta data.
     *
     * @return array
     */
    public function meta() : array
    {
        $meta = [];
        try {
            $col_count = $this->stmt->columnCount();
            for ($i = 0 ; $i < $col_count ; $i++) {
                $column                = $this->stmt->getColumnMeta($i);
                $meta[$column['name']] = $column;
                $meta[$i]              = $column;
            }
        } catch (\PDOException $e) {
            return [];
        }

        return $meta;
    }

    /**
     * Executes a prepared statement
     *
     * @param array|PdoParameter[] $params (default: [])
     * @return self
     * @throws DatabaseException|\PDOException
     */
    public function execute(array $params = []) : self
    {
        try {
            foreach ($params as $key => $param) {
                $param = $param instanceof PdoParameter ? $param : PdoParameter::str($param) ;
                $this->stmt->bindValue($key, $param->value, $param->type);
            }

            if (! $this->stmt->execute()) {
                throw $this->db->exception($this->stmt->errorInfo(), $this->stmt->queryString, $params);
            }
        } catch (\PDOException $e) {
            throw $this->db->exception($e, $this->stmt->queryString, $params);
        }
        $this->db->log($this->stmt->queryString, $params);
        return $this;
    }

    /**
     * Convert statment result set row to given class.
     *
     * @param mixed $row
     * @param string $class
     * @param array|null $meta info of this statement for performance in loop (default: null)
     * @param AnnotatedClass|null $ac annotated class of given class for performance in loop (default: null)
     * @return void
     */
    protected function convert($row, string $class, ?array $meta = null, ?AnnotatedClass $ac = null)
    {
        $meta = $meta ?? $this->meta();
        $ac   = $ac ?? new AnnotatedClass($class);
        $dm   = new $class();
        foreach ($row as $column => $value) {
            $am          = $ac->property($column);
            $type        = $am ? $am->annotation(PhpType::class) : null ;
            $dm->$column = $this->db->convertToPhp($value, $meta[$column] ?? [], $type ? $type->value : null);
        }
        if ($dm instanceof Entity) {
            $dm->origin(clone $dm->removeOrigin());
        }
        return $dm;
    }

    /**
     * Get all result set data as given class object list.
     *
     * @param string $class (default: 'stdClass')
     * @return ResultSet
     */
    public function all(string $class = 'stdClass') : ResultSet
    {
        $rs   = [];
        $meta = $this->meta();
        $ac   = new AnnotatedClass($class);
        while ($row = $this->stmt->fetch(\PDO::FETCH_ASSOC)) {
            $rs[] = $this->convert($row, $class, $meta, $ac);
        }
        return new ResultSet($rs);
    }

    /**
     * Get first result set data as given class object.
     *
     * @param string $class (default: 'stdClass')
     * @return mixed
     */
    public function first(string $class = 'stdClass')
    {
        return ($row = $this->stmt->fetch(\PDO::FETCH_ASSOC)) ? $this->convert($row, $class) : null ;
    }

    /**
     * Get all of given column data.
     *
     * @param string|int $column
     * @param string|null $type name of convert to type
     * @return ResultSet
     */
    public function allOf($column, ?string $type = null) : ResultSet
    {
        $rs   = [];
        $meta = $this->meta();
        while ($row = $this->stmt->fetch(is_int($column) ? \PDO::FETCH_NUM : \PDO::FETCH_ASSOC)) {
            $rs[] = $this->db->convertToPhp($row[$column] ?? null, $meta[$column] ?? [], $type);
        }
        return new ResultSet($rs);
    }

    /**
     * Get first of given column data.
     *
     * @param string|int $column
     * @param string|null $type name of convert to type
     * @return mixed
     */
    public function firstOf($column, ?string $type = null)
    {
        return ($row = $this->stmt->fetch(is_int($column) ? \PDO::FETCH_NUM : \PDO::FETCH_ASSOC)) ? $this->db->convertToPhp($row[$column] ?? null, $this->meta()[$column] ?? [], $type) : null ;
    }

    /**
     * Get the affected rows count of latest SQL.
     *
     * @return integer
     */
    public function affectedRows() : int
    {
        return $this->stmt->rowCount();
    }

    /**
     * Apply given callback function to all result set data.
     * This function can specify how to receive data in the type hint of the first argument of the callback function.
     * If the callback function return 'false' then immediately exit loop.
     *
     * @param callable $callback function(Class $row) : bool {}
     * @return self
     */
    public function each(callable $callback) : self
    {
        $meta  = $this->meta();
        $class = Reflector::getTypeHintOf($callback, 0) ?? 'stdClass';
        $ac    = new AnnotatedClass($class);
        while ($row = $this->stmt->fetch(\PDO::FETCH_ASSOC)) {
            if (call_user_func($callback, $this->convert($row, $class, $meta, $ac)) === false) {
                break;
            }
        }
        return $this;
    }

    /**
     * Filter the result set using the given callback.
     * This function can specify how to receive data in the type hint of the first argument of the callback function.
     *
     * @param callable $callback function(Class $row) : bool {}
     * @return ResultSet
     */
    public function filter(callable $callback) : ResultSet
    {
        $filtered = [];
        $meta     = $this->meta();
        $class    = Reflector::getTypeHintOf($callback, 0) ?? 'stdClass';
        $ac       = new AnnotatedClass($class);
        while ($row = $this->stmt->fetch(\PDO::FETCH_ASSOC)) {
            $item = $this->convert($row, $class, $meta, $ac);
            if (call_user_func($callback, $item)) {
                $filtered[] = $item;
            }
        }
        return new ResultSet($filtered);
    }

    /**
     * Run a map over each of the items.
     * This function can specify how to receive data in the type hint of the first argument of the callback function.
     *
     * @param callable $callback function(Class $row) : mixed {}
     * @return ResultSet
     */
    public function map(callable $callback) : ResultSet
    {
        $map   = [];
        $meta  = $this->meta();
        $class = Reflector::getTypeHintOf($callback, 0) ?? 'stdClass';
        $ac    = new AnnotatedClass($class);
        while ($row = $this->stmt->fetch(\PDO::FETCH_ASSOC)) {
            $map[] = call_user_func($callback, $this->convert($row, $class, $meta, $ac));
        }
        return new ResultSet($map);
    }

    /**
     * Reduce the result set to a single value.
     * This function can specify how to receive data in the type hint of the first argument of the reducer function.
     *
     * @param callable $reducer function(Class $row, $carry) : mixed {}
     * @param mixed $initial (default: null)
     * @return mixed
     */
    public function reduce(callable $reducer, $initial = null)
    {
        $carry = $initial;
        $meta  = $this->meta();
        $class = Reflector::getTypeHintOf($reducer, 0) ?? 'stdClass';
        $ac    = new AnnotatedClass($class);
        while ($row = $this->stmt->fetch(\PDO::FETCH_ASSOC)) {
            $carry = call_user_func($reducer, $this->convert($row, $class, $meta, $ac), $carry);
        }
        return $carry;
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator()
    {
        return $this->stmt;
    }

    /**
     * Close this statement.
     *
     * @return boolean
     */
    public function close() : bool
    {
        return $this->stmt->closeCursor();
    }
}
