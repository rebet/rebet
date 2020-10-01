<?php
namespace Rebet\Cache\Adapter\Symfony;

use Rebet\Tools\Unit;
use Rebet\Database\Dao;
use Symfony\Component\Cache\Adapter\PdoAdapter as SymfonyPdoAdapter;
use Symfony\Component\Cache\Marshaller\MarshallerInterface;

/**
 * Memcached Adapter Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class PdoAdapter extends AbstractSymfonyAdapter
{
    /**
     * Create PDO Adapter
     *
     * You can either pass an existing database connection as PDO instance or a database name string that configured `Dao.dbs.{name}`.
     * The cache table is created automatically use the Symfony\Component\Cache\Adapter\PdoAdapter::createTable() method.
     *
     * List of available options:
     *  * db_table       : The name of the table                    [default: cache_items]
     *  * db_id_col      : The column where to store the cache id   [default: cache_item_id]
     *  * db_data_col    : The column where to store the cache data [default: data]
     *  * db_lifetime_col: The column where to store the lifetime   [default: lifetime]
     *  * db_time_col    : The column where to store the timestamp  [default: time]
     *
     * @param string|\PDO|null $db name a string of Dao.dbs.* configuration or a \PDO instance. (default: null for use default database of `Dao.dbs` configure)
     * @param array $options for connect memcached. (default: [])
     * @param string $namespace (default: '')
     * @param int|string $default_lifetime that time unit labeled string like '12min', or int seconds. (default: 0)
     * @param MarshallerInterface|null $marshaller (default: null)
     * @param bool $taggable (default: false)
     * @param null|string|CacheItemPoolInterface $tags_pool name that `Cache.stores.{name}` or CacheItemPoolInterface instance when taggable is true. (default: null for use given $adapter as it is)
     * @param float $known_tag_versions_ttl when taggable is true. (default: 0.15)
     */
    public function __construct($db = null, string $namespace = '', $default_lifetime = 0, array $options = [], MarshallerInterface $marshaller = null, bool $taggable = false, $tags_pool = null, $known_tag_versions_ttl = 0.15)
    {
        $options = array_merge([
            'db_table'        => 'cache_items',
            'db_id_col'       => 'cache_item_id',
            'db_data_col'     => 'data',
            'db_lifetime_col' => 'lifetime',
            'db_time_col'     => 'time',
        ], $options);

        parent::__construct(
            new SymfonyPdoAdapter(
                is_string($db) ? Dao::db($db)->pdo() : $db,
                $namespace,
                Unit::of(Unit::TIME)->convert($default_lifetime)->toInt(),
                $options,
                $marshaller
            ),
            $taggable,
            $tags_pool,
            $known_tag_versions_ttl
        );
    }
}
