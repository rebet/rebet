<?php

declare(strict_types=1);

namespace Rebet\Http\Session\Storage\Handler;

use Override;
use Rebet\Tools\Config\Configurable;
use Rebet\Tools\Utility\Env;
use Symfony\Component\Cache\Adapter\RedisAdapter as SymfonyRedisAdapter;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\RedisSessionHandler as SymfonyRedisSessionHandler;

/**
 * Redis Session Handler Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class RedisSessionHandler extends SymfonyRedisSessionHandler
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
            'dsn'     => Env::promise('SESSION_REDIS_DSN'),
            'prefix'  => 'rbt-s:',
            'ttl'     => fn(): int => (int) ini_get('session.gc_maxlifetime') ?: 86400,
            'options' => [],
        ];
    }

    /**
     * Create a redis session handler.
     *
     * The redis connection is created by SymfonyRedisAdapter::createConnection().
     *
     * @param string|null          $dsn     The DSN of the redis server (ex. redis://localhost:6379/0) (default: depend on configure)
     * @param string|null          $prefix  The prefix to use for the redis keys in order to avoid collision (default: depend on configure)
     * @param int|\Closure|null    $ttl     The time to live in seconds (default: depend on configure, fn() => session.gc_maxlifetime ?: 86400 (evaluated on each use))
     * @param array<string, mixed> $options The connection options of SymfonyRedisAdapter::createConnection() (default: depend on configure)
     *                                      - class, persistent, persistent_id, timeout, read_timeout, retry_interval, tcp_keepalive, lazy, redis_cluster, redis_sentinel, dbindex, failover
     *                                      - and the Predis-specific connection parameters when \Predis\Client is used
     */
    public function __construct(
        string|null $dsn = null,
        string|null $prefix = null,
        \Closure|int|null $ttl = null,
        array|null $options = null,
    ) {
        parent::__construct(
            SymfonyRedisAdapter::createConnection($dsn ?? static::config('dsn'), $options ?? static::config('options', false, [])),
            [
                'prefix' => $prefix ?? static::config('prefix'),
                'ttl'    => $ttl ?? static::config('ttl', false),
            ],
        );
    }
}
