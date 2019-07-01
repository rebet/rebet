<?php
namespace Rebet\Database\Exception;

use Rebet\Common\Exception\RuntimeException;
use Rebet\Common\Strings;

/**
 * Database Exception Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class DatabaseException extends RuntimeException
{
    /**
     * SQL state code
     *
     * @var string
     */
    protected $sql_state = null;

    /**
     * Create a database exception.
     *
     * @param string $message
     * @param \Throwable|null $previous
     */
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }

    /**
     * Set SQL state code.
     */
    public function sqlState(string $sql_state) : DatabaseException
    {
        $this->sql_state = $sql_state;
        return $this;
    }

    /**
     * Get SQL state code.
     *
     * @return string|null
     */
    public function getSqlState() : ?string
    {
        return $this->sql_state;
    }

    /**
      * Create the exception using given PDO error info.
      *
      * @param array|\PDOException $error
      * @param string|null $sql (default: null)
      * @param array $param (default: [])
      * @return self
      */
    public static function from($error, ?string $sql = null, array $params = []) : self
    {
        $error_info = is_array($error) ? $error : $error->errorInfo ;
        $sql_state  = $error_info[0] ?? null ;
        $code       = $error_info[1] ?? null ;
        $message    = $error_info[2] ?? 'Unkown error occured.' ;

        $sql  = empty($sql)    ? '' : "\n--- [SQL] ---\n{$sql}";
        $sql .= empty($params) ? '' : "\n-- [PARAM] --\n".Strings::stringify($params) ;
        $sql .= empty($sql)    ? '' : "\n-------------\n" ;

        $e = static::by("[".($sql_state ?? '-----').($code ? "({$code})" : "")."] {$message}{$sql}")->sqlState($sql_state)->code($code)->appendix($error_info);
        if ($error instanceof \Throwable) {
            $e->caused($error);
        }
        return $e;
    }
}
