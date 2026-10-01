<?php

declare(strict_types=1);

namespace Rebet\Http\Session\Storage\Handler;

use MongoDB\Client;
use Override;
use Rebet\Tools\Config\Configurable;
use Rebet\Tools\Utility\Env;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\MongoDbSessionHandler as SymfonyMongoDbSessionHandler;

/**
 * Mongo Db Session Handler Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class MongoDbSessionHandler extends SymfonyMongoDbSessionHandler
{
    use Configurable;

    /**
     * {@inheritDoc}
     * @see https://github.com/rebet/rebet/blob/master/skeltons/app/core/configs/http.lp.php
     */
    #[Override]
    public static function defaultConfig()
    {
        return [
            'uri'            => Env::promise('SESSION_MONGODB_URI'), // null for the default URI of MongoDB\Client
            'uri_options'    => [],
            'driver_options' => [],
            'options'        => [
                'database'     => 'rebet',
                'collection'   => 'sessions',
                'id_field'     => '_id',
                'data_field'   => 'data',
                'time_field'   => 'time',
                'expiry_field' => 'expires_at',
                'ttl'          => fn(): int => (int) ini_get('session.gc_maxlifetime') ?: 86400,
            ],
        ];
    }

    /**
     * Create a mongodb session handler.
     *
     * The mongodb connection is created by MongoDB\Client using the given $uri, $uri_options and $driver_options.
     *
     * @param string|null               $uri            The MongoDB connection string (default: depend on configure, null for the default URI of MongoDB\Client)
     * @param array<string, mixed>|null $uri_options    The additional connection string options of MongoDB\Client (default: depend on configure)
     * @param array<string, mixed>|null $driver_options The driver-specific options of MongoDB\Client (default: depend on configure)
     * @param array<string, mixed>|null $options        The options of Symfony's MongoDbSessionHandler, they are merged into the `options` configuration (default: depend on configure)
     *                                                  - database     : string The name of the database                        [default: rebet]
     *                                                  - collection   : string The name of the collection                      [default: sessions]
     *                                                  - id_field     : string The field name for storing the session id       [default: _id]
     *                                                  - data_field   : string The field name for storing the session data     [default: data]
     *                                                  - time_field   : string The field name for storing the timestamp        [default: time]
     *                                                  - expiry_field : string The field name for storing the expiry-timestamp [default: expires_at]
     *                                                  - ttl          : int|\Closure|null The time to live in seconds          [default: fn() => session.gc_maxlifetime ?: 86400 (evaluated on each use)]
     */
    public function __construct(
        string|null $uri = null,
        array|null $uri_options = null,
        array|null $driver_options = null,
        array|null $options = null,
    ) {
        parent::__construct(
            new Client(
                $uri ?? static::config('uri', false),
                $uri_options ?? static::config('uri_options', false, []),
                $driver_options ?? static::config('driver_options', false, []),
            ),
            array_merge(static::config('options', false, []), $options ?? []),
        );
    }
}
