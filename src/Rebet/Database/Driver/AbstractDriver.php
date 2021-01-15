<?php
namespace Rebet\Database\Driver;

use PDO;
use Rebet\Database\Analysis\Analyzer;
use Rebet\Database\Analysis\BuiltinAnalyzer;
use Rebet\Database\Exception\DatabaseException;
use Rebet\Database\PdoParameter;
use Rebet\Tools\Config\Configurable;
use Rebet\Tools\DateTime\Date;
use Rebet\Tools\Enum\Enum;
use Rebet\Tools\Math\Decimal;
use Rebet\Tools\Utility\Arrays;

/**
 * Driver Interface
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
abstract class AbstractDriver implements Driver
{
    use Configurable;

    /**
     * PDO driver name that this driver will be support.
     * NOTE: Please override this property in the sub class.
     *
     * @var string
     */
    protected const SUPPORTED_PDO_DRIVER = null;

    /**
     * Identifier quortes.
     * NOTE: Please override this property in the sub class, if needed. (default: ['"', '"'])
     *
     * @var string[]
     */
    protected const IDENTIFIER_QUOTES = ['"', '"'];

    /**
     * PDO date type format.
     * NOTE: Please override this property in the sub class, if needed. (default: 'Y-m-d')
     *
     * @var string
     */
    protected const PDO_DATE_FORMAT = 'Y-m-d';

    /**
     * PDO date time type format.
     * NOTE: Please override this property in the sub class, if needed. (default: 'Y-m-d H:i:s')
     *
     * @var string
     */
    protected const PDO_DATETIME_FORMAT = 'Y-m-d H:i:s';


    /**
     * @var \PDO driver
     */
    protected $pdo;

    /**
     * {@inheritDoc}
     */
    public static function create(string $dsn, ?string $user = null, ?string $password = null, array $options = []) : Driver
    {
        return new static(new \PDO($dsn, $user, $password), $options);
    }

    /**
     * Create driver instance.
     *
     * @param \PDO $pdo
     */
    protected function __construct(\PDO $pdo, array $driver_options = [])
    {
        $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        if ($driver !== static::SUPPORTED_PDO_DRIVER) {
            throw new DatabaseException("Invalid PDO driver '{$driver}' was given. This driver require '".static::SUPPORTED_PDO_DRIVER."' PDO driver.");
        }
        foreach (array_replace(static::config("options.pdo", false) ?? [], $driver_options) as $key => $value) {
            $pdo->setAttribute($key, $value);
        }
        $this->pdo = $pdo;
    }

    /**
     * {@inheritDoc}
     */
    public function pdo() : \PDO
    {
        if ($this->closed()) {
            throw new DatabaseException("[".static::SUPPORTED_PDO_DRIVER."] Database connection was lost.");
        }
        return $this->pdo;
    }

    /**
     * {@inheritDoc}
     */
    public function serverVersion() : string
    {
        return $this->pdo()->getAttribute(\PDO::ATTR_SERVER_VERSION);
    }

    /**
     * {@inheritDoc}
     */
    public function clientVersion() : string
    {
        $version = $this->pdo()->getAttribute(\PDO::ATTR_CLIENT_VERSION);
        return is_array($version) ? Arrays::implode($version, '; ', '=') : $version ;
    }

    /**
     * Create Database exception from given error information.
     *
     * @param array|\PDOException $error
     * @param string|null $sql (default: null)
     * @param array $param (default: [])
     * @return DatabaseException
     */
    protected function exception($error, ?string $sql = null, array $params = []) : DatabaseException
    {
        return DatabaseException::from('pdo:'.static::SUPPORTED_PDO_DRIVER, $error, $sql, $params);
    }

    /**
     * {@inheritDoc}
     */
    public function quote(string $string, int $parameter_type = \PDO::PARAM_STR) : string
    {
        return $this->pdo()->quote($string, $parameter_type);
    }

    /**
     * {@inheritDoc}
     */
    public function quoteIdentifier(string $identifier) : string
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $identifier)) {
            return $identifier;
        }

        return static::IDENTIFIER_QUOTES[0].$identifier.static::IDENTIFIER_QUOTES[1];
    }

    /**
     * {@inheritDoc}
     */
    public function begin() : string
    {
        try {
            if (!$this->pdo()->beginTransaction()) {
                throw $this->exception($this->pdo()->errorInfo());
            }
            return "BEGIN";
        } catch (\PDOException $e) {
            throw $this->exception($e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function savepoint(string $name) : string
    {
        $this->exec($sql = $this->buildSavepointSql($name));
        return $sql;
    }

    /**
     * Build savepoint SQL.
     *
     * @param string $name of savepoint
     * @return string of savepoint SQL
     */
    protected function buildSavepointSql(string $name) : string
    {
        return "SAVEPOINT {$name}";
    }

    /**
     * {@inheritDoc}
     */
    public function commit() : string
    {
        try {
            if (!$this->pdo()->commit()) {
                throw $this->exception($this->pdo()->errorInfo());
            }
            return "COMMIT";
        } catch (\PDOException $e) {
            throw $this->exception($e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function rollback(?string $savepoint = null, bool $quiet = true) : ?string
    {
        try {
            if ($savepoint) {
                $this->exec($sql = $this->buildRollbackToSavepointSql($savepoint));
                return $sql;
            }

            if (!$this->pdo()->rollBack()) {
                throw $this->exception($this->pdo()->errorInfo());
            }
            return "ROLLBACK";
        } catch (\Exception $e) {
            if (!$quiet) {
                throw $e instanceof \PDOException ? $this->exception($e) : $e ;
            }
        }
        return null;
    }

    /**
     * Build rollback to savepoint SQL.
     *
     * @param string $name of savepoint
     * @return string of rollback to savepoint SQL.
     */
    protected function buildRollbackToSavepointSql(string $name) : string
    {
        return "ROLLBACK TO SAVEPOINT {$name}";
    }

    /**
     * {@inheritDoc}
     */
    public function exec(string $sql) : int
    {
        if (($affected_rows = $this->pdo()->exec($sql)) === false) {
            throw $this->exception($this->pdo()->errorInfo(), $sql);
        }
        return $affected_rows;
    }

    /**
     * {@inheritDoc}
     */
    public function prepare(string $sql, array $driver_options = []) : \PDOStatement
    {
        try {
            $options = array_replace(static::config("options.statement", false) ?? [], $driver_options);
            $stmt    = $this->pdo()->prepare($sql, $options);
            if (!$stmt) {
                throw $this->exception($this->pdo()->errorInfo(), $sql);
            }
            return $stmt;
        } catch (\PDOException $e) {
            throw $this->exception($e, $sql);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function lastInsertId(?string $name = null) : string
    {
        return $this->pdo()->lastInsertId($name);
    }

    /**
     * {@inheritDoc}
     */
    public function truncate(string $table_name, ?bool $with_vacuum = true) : string
    {
        $quoted_table_name = $this->quoteIdentifier($table_name);
        $this->exec($sql = "TRUNCATE TABLE {$quoted_table_name}");
        return $sql;
    }

    /**
     * {@inheritDoc}
     */
    public function close() : void
    {
        if ($this->pdo) {
            $this->rollback();
            $this->pdo = null;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function closed() : bool
    {
        return $this->pdo === null;
    }

    /**
     * {@inheritDoc}
     */
    public function appendLimitOffset(string $sql, ?int $limit, ?int $offset = null) : string
    {
        $limit  = $limit  ? " LIMIT {$limit}"   : "" ;
        $offset = $offset ? " OFFSET {$offset}" : "" ;
        return "{$sql}{$limit}{$offset}";
    }

    /**
     * {@inheritDoc}
     */
    public function appendForUpdate(string $sql) : string
    {
        return "{$sql} FOR UPDATE";
    }

    /**
     * {@inheritDoc}
     */
    public function toPdoType($value) : PdoParameter
    {
        if ($value instanceof Enum) {
            $value = $value->value;
        }

        switch (true) {
            case $value instanceof PdoParameter:       return $value;
            case $value === null:                      return PdoParameter::null();
            case is_bool($value):                      return PdoParameter::bool($value);
            case is_int($value):                       return PdoParameter::int($value);
            case is_float($value):                     return PdoParameter::str($value);
            case is_resource($value):                  return PdoParameter::lob(stream_get_contents($value));
            case $value instanceof Date:               return PdoParameter::str($value->format(static::PDO_DATE_FORMAT));
            case $value instanceof \DateTimeInterface: return PdoParameter::str($value->format(static::PDO_DATETIME_FORMAT)); // Do NOT convert before Date
            case $value instanceof Decimal:            return PdoParameter::str($value->normalize()->format(true, '.', ''));
        }

        return PdoParameter::str($value);
    }

    /**
     * {@inheritDoc}
     */
    public function analyzer(string $sql) : Analyzer
    {
        return new BuiltinAnalyzer($sql);
    }
}
