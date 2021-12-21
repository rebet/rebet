<?php
namespace Rebet\Http\Session\Storage\Handler;

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

    public static function defaultConfig()
    {
        return [
            'options' => [
                'db_table'        => 'sessions',                                 // The name of the table
                'db_id_col'       => 'session_id',                               // The column where to store the session id
                'db_data_col'     => 'session_data',                             // The column where to store the session data
                'db_lifetime_col' => 'session_lifetime',                         // The column where to store the lifetime
                'db_time_col'     => 'session_time',                             // The column where to store the timestamp
                'lock_mode'       => DatabaseSessionHandler::LOCK_TRANSACTIONAL, // The strategy for locking, see constants
            ],
        ];
    }

    public function __construct(string $db = null, array $options = []) {
        parent::__construct(Dao::db($db)->pdo(), array_merge(static::config('options'), $options));
    }
}
