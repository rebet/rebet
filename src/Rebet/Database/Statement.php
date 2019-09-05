<?php
namespace Rebet\Database;

use Rebet\Annotation\AnnotatedClass;
use Rebet\Database\Annotation\Type;
use Rebet\Database\DataModel\Entity;
use Rebet\Database\Exception\DatabaseException;

/**
 * Statement Class
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
        $dto  = new $class();
        foreach ($row as $column => $value) {
            $am           = $ac->property($column);
            $type         = $am ? $am->annotation(Type::class) : null ;
            $dto->$column = $this->db->convertToPhp($value, $meta[$column] ?? [], $type ? $type->value : null);
        }
        if ($dto instanceof Entity) {
            $dto->origin(clone $dto);
        }
        return $dto;
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
        foreach ($this->stmt as $row) {
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
        $meta = $this->meta();
        $ac   = new AnnotatedClass($class);
        foreach ($this->stmt as $row) {
            return $this->convert($row, $class, $meta, $ac);
        }
        return null;
    }

    /**
     * Get all of given column data.
     *
     * @param string $column
     * @param string|null $type name of convert to type
     * @return ResultSet
     */
    public function allOf(string $column, ?string $type = null) : ResultSet
    {
        $rs   = [];
        $meta = $this->meta();
        foreach ($this->stmt as $row) {
            $rs[] = $this->db->convertToPhp($row[$column] ?? null, $meta[$column] ?? [], $type);
        }
        return new ResultSet($rs);
    }

    /**
     * Get first of given column data.
     *
     * @param string $column
     * @param string|null $type name of convert to type
     * @return mixed
     */
    public function firstOf(string $column, ?string $type = null)
    {
        $meta = $this->meta();
        foreach ($this->stmt as $row) {
            return $this->db->convertToPhp($row[$column] ?? null, $meta[$column] ?? [], $type);
        }
        return null;
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
     * It checks the result set has rows or not.
     *
     * @return boolean
     */
    public function empty() : bool
    {
        return $this->stmt->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_FIRST) !== false ;
    }

    /**
     * Apply given callback function to all result set data as given class object.
     * If the callback function return 'false' then immediately exit loop.
     *
     * @param callable $callback function(Class $row) : bool {}
     * @param string $class (default: 'stdClass')
     * @return self
     */
    public function each(callable $callback, string $class = 'stdClass') : self
    {
        $meta = $this->meta();
        $ac   = new AnnotatedClass($class);
        foreach ($this->stmt as $row) {
            if (call_user_func($callback, $this->convert($row, $class, $meta, $ac)) === false) {
                break;
            }
        }
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator()
    {
        return $this->stmt;
    }
}
