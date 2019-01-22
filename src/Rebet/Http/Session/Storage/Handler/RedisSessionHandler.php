<?php
namespace Rebet\Http\Session\Storage\Handler;

use Rebet\Config\Configurable;
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

    public static function defaultConfig()
    {
        return [
            'prefix' => 'rebet',
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @param \Redis|\RedisArray|\RedisCluster|\Predis\Client|RedisProxy  $redis
     * @param array $options (default: depend on confiugre)
     */
    public function __construct($redis, array $options = [])
    {
        parent::__construct($redis, array_merge(static::config(), $options));
    }
}
