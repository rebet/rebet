<?php

declare(strict_types=1);

namespace Rebet\Http\Session\Storage\Handler;

use Override;
use Rebet\Database\Dao;
use Rebet\Tools\Config\Configurable;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler as SymfonyPdoSessionHandler;

/**
 * Database Session Handler Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class DatabaseSessionHandler extends SymfonyPdoSessionHandler
{
    use Configurable;

    #[Override]
    public static function defaultConfig()
    {
        return [
            'db'      => null,    // The name of the database (null for the default database of Dao)
            'options' => [
                'db_table'        => 'sessions',                                 // The name of the table
                'db_id_col'       => 'session_id',                               // The column where to store the session id
                'db_data_col'     => 'session_data',                             // The column where to store the session data
                'db_lifetime_col' => 'session_lifetime',                         // The column where to store the lifetime
                'db_time_col'     => 'session_time',                             // The column where to store the timestamp
                'lock_mode'       => DatabaseSessionHandler::LOCK_TRANSACTIONAL, // The strategy for locking, see constants
                'ttl'             => fn(): int => (int) ini_get('session.gc_maxlifetime') ?: 86400,
            ],
        ];
    }

    /**
     * Create a database session handler.
     *
     * The PDO connection is taken from the database of Rebet\Database\Dao, so the session is
     * stored in the table of that database.
     *
     * @param string|null          $db      The name of the database defined in the database configuration (default: depend on configure, null for the default database)
     * @param array<string, mixed> $options The options of Symfony's PdoSessionHandler, they are merged into the `options` configuration (default: depend on configure)
     *                                      - db_table        : string The name of the table                                        [default: sessions]
     *                                      - db_id_col       : string The column where to store the session id                     [default: session_id]
     *                                      - db_data_col     : string The column where to store the session data                   [default: session_data]
     *                                      - db_lifetime_col : string The column where to store the lifetime                       [default: session_lifetime]
     *                                      - db_time_col     : string The column where to store the timestamp                      [default: session_time]
     *                                      - lock_mode       : int    The strategy for locking, see DatabaseSessionHandler::LOCK_* [default: LOCK_TRANSACTIONAL]
     *                                      - ttl             : int|\Closure|null The time to live in seconds                       [default: fn() => session.gc_maxlifetime ?: 86400 (evaluated on each use)]
     *                                      (db_username, db_password and db_connection_options are not used because the PDO is given.)
     */
    public function __construct(string|null $db = null, array $options = [])
    {
        parent::__construct(Dao::db($db ?? static::config('db', false))->pdo(), array_merge(static::config('options'), $options));
    }
}
