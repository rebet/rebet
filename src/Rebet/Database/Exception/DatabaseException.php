<?php
namespace Rebet\Database\Exception;

use Rebet\Common\Exception\RuntimeException;
use Rebet\Common\Getsetable;
use Rebet\Common\Strings;
use Rebet\Database\Database;

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
    use Getsetable;

    /**
     * SQL state code
     *
     * @var string
     */
    protected $sql_state = null;

    /**
     * @var Database
     */
    protected $db = null;

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
     * Get/Set SQL state code.
     *
     * @return DatabaseException|string|null
     */
    public function sqlState(?string $sql_state = null)
    {
        return $this->getset('sql_state', $sql_state);
    }

    /**
     * Get/Set Database
     *
     * @return DatabaseException|Database|null
     */
    public function db($db = null)
    {
        return $this->getset('db', $db);
    }

    /**
      * Create the exception using given PDO error info.
      *
      * @param Database|null $db
      * @param array|\PDOException $error
      * @param string|null $sql (default: null)
      * @param array $param (default: [])
      * @return self
      */
    public static function from(Database $db, $error, ?string $sql = null, array $params = []) : self
    {
        $error_info = is_array($error) ? $error : $error->errorInfo ;
        $sql_state  = $error_info[0] ?? 'UNKOWN' ;
        $code       = $error_info[1] ?? null ;
        $message    = $error_info[2] ?? 'Unkown error occured.' ;
        $name       = $db->name();
        $driver     = $db->closed() ? 'unkown' : $db->driverName() ;

        $sql  = empty($sql)    ? '' : "\n--- [SQL] ---\n{$sql}";
        $sql .= empty($params) ? '' : "\n-- [PARAM] --\n".Strings::stringify($params) ;
        $sql .= empty($sql)    ? '' : "\n-------------\n" ;

        $e = (new static("[{$name}/{$driver}: ".($sql_state ?? '-----').($code ? "({$code})" : "")."] {$message}{$sql}"))->db($db)->sqlState($sql_state)->code($code)->appendix($error_info);
        if ($error instanceof \Throwable) {
            $e->caused($error);
        }
        return $e;
    }
}
