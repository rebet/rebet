<?php
namespace Rebet\Cache\Adapter\Symfony;

use Rebet\Tools\Math\Unit;
use Symfony\Component\Cache\Adapter\RedisAdapter as SymfonyRedisAdapter;
use Symfony\Component\Cache\Marshaller\MarshallerInterface;

/**
 * Memcached Adapter Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class RedisAdapter extends AbstractSymfonyAdapter
{
    /**
     * Create Memcached Adapter
     *
     * List of available options:
     *  * class          : Specifies the connection library to return, either \Redis or \Predis\Client.                                                                [default: null for \Redis if can use, otherwise \Predis\Client]
     *  * persistent     : Enables(=1) or disables(=0) use of persistent connections.                                                                                  [default: 0]
     *  * persistent_id  : Specifies the persistent id string to use for a persistent connection.                                                                      [default: null]
     *  * timeout        : Specifies the time (in seconds) used to connect to a Redis server before the connection attempt times out.                                  [default: 30]
     *  * read_timeout   : Specifies the time (in seconds) used when performing read operations on the underlying network resource before the operation times out.     [default: 0]
     *  * retry_interval : Specifies the delay (in milliseconds) between reconnection attempts in case the client loses connection with the server.                    [default: 0]
     *  * tcp_keepalive  : Specifies the TCP-keepalive timeout (in seconds) of the connection. This requires phpredis v4 or higher and a TCP-keepalive enabled server. [default: 0]
     *  * lazy           : Enables or disables lazy connections to the backend.                                                                                        [default: false]
     *  * redis_cluster  : Use RedisCluster or not.                                                                                                                    [default: false]
     *  * redis_sentinel : Redis Sentinel, which provides high availability for Redis. Use the redis_sentinel parameter to set the name of your service group.         [default: null]
     *  * dbindex        : Redis database index that will use. You can set this option by DSN too.                                                                     [default: 0]
     *  * failover       : Set the RedisCluster failover option that 'none', 'error', 'distribute' or 'slaves'.                                                        [default: 'none']
     *
     * NOTE: When using the Predis library some additional Predis-specific options are available. Reference the Predis Connection Parameters documentation for more information.
     *       @see https://github.com/predis/predis/wiki/Connection-Parameters#list-of-connection-parameters
     *
     * @param array[]|string|string[] $dsn An array of dsns, a DSN, or an array of DSNs formatted below (default: 'redis://localhost')
     *                                     - redis://[pass@][ip|host|socket[:port]][/db-index]
     *                                     - redis:?host[redis1:26379]&host[redis2:26379]&host[redis3:26379]&redis_sentinel=mymaster
     * @param array $options for connect redis. (default: [])  @see Symfony\Component\Cache\Traits\RedisTrait::$defaultConnectionOptions
     * @param string $namespace (default: '')
     * @param int|string $default_lifetime that time unit labeled string like '12min', or int seconds. (default: 0)
     * @param MarshallerInterface|null $marshaller (default: mull)
     * @param bool $taggable (default: false)
     * @param null|string|CacheItemPoolInterface $tags_pool name that `Cache.stores.{name}` or CacheItemPoolInterface instance when taggable is true. (default: null for use given $adapter as it is)
     * @param float $known_tag_versions_ttl when taggable is true. (default: 0.15)
     */
    public function __construct($dsn = 'redis://localhost', array $options = [], string $namespace = '', $default_lifetime = 0, MarshallerInterface $marshaller = null, bool $taggable = false, $tags_pool = null, $known_tag_versions_ttl = 0.15)
    {
        parent::__construct(
            new SymfonyRedisAdapter(
                SymfonyRedisAdapter::createConnection($dsn, $options),
                $namespace,
                Unit::of(Unit::TIME)->convert($default_lifetime)->toInt(),
                $marshaller
            ),
            $taggable,
            $tags_pool,
            $known_tag_versions_ttl
        );
    }
}
