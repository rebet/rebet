<?php

declare(strict_types=1);

namespace Rebet\Http\Session\Storage\Handler;

use Override;
use Rebet\Tools\Config\Configurable;
use Rebet\Tools\Utility\Env;
use Symfony\Component\Cache\Adapter\MemcachedAdapter as SymfonyMemcachedAdapter;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\MemcachedSessionHandler as SymfonyMemcachedSessionHandler;

/**
 * Memcached Session Handler Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class MemcachedSessionHandler extends SymfonyMemcachedSessionHandler
{
    use Configurable;

    /**
     * {@inheritDoc}
     * @see https://github.com/rebet/rebet/blob/master/skeltons/app/core/config/http.lp.php
     */
    #[Override]
    public static function defaultConfig()
    {
        return [
            'dsn'     => Env::promise('SESSION_MEMCACHED_DSN'),
            'prefix'  => 'rbt-s:',
            'ttl'     => fn(): int => (int) ini_get('session.gc_maxlifetime') ?: 86400,
            'options' => [],
        ];
    }

    /**
     * Create a memcached session handler.
     *
     * The memcached connection is created by SymfonyMemcachedAdapter::createConnection().
     *
     * @param string|string[]|null $dsn     The DSN(s) of the memcached servers (ex. memcached://localhost:11211) (default: depend on configure)
     * @param string|null          $prefix  The prefix to use for the memcached keys in order to avoid collision (default: depend on configure)
     * @param int|\Closure|null    $ttl     The time to live in seconds (default: depend on configure, fn() => session.gc_maxlifetime ?: 86400 (evaluated on each use))
     * @param array<string, mixed> $options The connection options of SymfonyMemcachedAdapter::createConnection() (default: depend on configure)
     *                                      - username, password, persistent_id, weight, lazy
     *                                      - Memcached::OPT_* names without 'OPT_' prefix (ex. serializer, hash, distribution, libketama_compatible,
     *                                      no_block, tcp_nodelay, tcp_keepalive, connect_timeout, send_timeout, recv_timeout, poll_timeout, compression)
     */
    public function __construct(
        array|string|null $dsn = null,
        string|null $prefix = null,
        \Closure|int|null $ttl = null,
        array|null $options = null,
    ) {
        parent::__construct(
            SymfonyMemcachedAdapter::createConnection($dsn ?? static::config('dsn'), $options ?? static::config('options', false, [])),
            [
                'prefix' => $prefix ?? static::config('prefix'),
                'ttl'    => $ttl ?? static::config('ttl', false),
            ],
        );
    }
}
