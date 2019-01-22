<?php
namespace Rebet\Http\Session\Storage\Handler;

use Rebet\Config\Configurable;
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

    public static function defaultConfig()
    {
        return [
            'database'     => null,
            'collection'   => null,
            'id_field'     => '_id',
            'data_field'   => 'data',
            'time_field'   => 'time',
            'expiry_field' => 'expires_at',
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @param \MongoDB\Client $mongo
     * @param array $options (default: depend on configure)
     */
    public function __construct(\MongoDB\Client $mongo, array $options = [])
    {
        parent::__construct($mongo, array_merge(static::config(), $options));
    }
}
