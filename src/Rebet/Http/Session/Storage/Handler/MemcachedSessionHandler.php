<?php
namespace Rebet\Http\Session\Storage\Handler;

use Rebet\Tools\Config\Configurable;
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

    public static function defaultConfig()
    {
        return [
            'expiretime' => 86400,
            'prefix'     => 'rebet',
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @param \Memcached $memcached
     * @param array|null $options (default: depenf on configure)
     */
    public function __construct(\Memcached $memcached, array $options = [])
    {
        parent::__construct($memcached, array_merge(static::config(), $options));
    }
}
