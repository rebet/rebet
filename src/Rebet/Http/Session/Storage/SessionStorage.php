<?php
namespace Rebet\Http\Session\Storage;

use Rebet\Config\Configurable;
use Rebet\Http\Session\Storage\Bag\MetadataBag;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

/**
 * Session Storage Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class SessionStorage extends NativeSessionStorage
{
    use Configurable;

    public static function defaultConfig()
    {
        return [
            'handler' => null,
            'options' => [
                'cache_expire'             => null,
                'cache_limiter'            => null,
                'cookie_domain'            => null,
                'cookie_httponly'          => null,
                'cookie_lifetime'          => null,
                'cookie_path'              => null,
                'cookie_secure'            => null,
                'gc_divisor'               => null,
                'gc_maxlifetime'           => null,
                'gc_probability'           => null,
                'lazy_write'               => null,
                'name'                     => null,
                'referer_check'            => null,
                'serialize_handler'        => null,
                'use_strict_mode'          => null,
                'use_cookies'              => null,
                'use_only_cookies'         => null,
                'use_trans_sid'            => null,
                'upload_progress.enabled'  => null,
                'upload_progress.cleanup'  => null,
                'upload_progress.prefix'   => null,
                'upload_progress.name'     => null,
                'upload_progress.freq'     => null,
                'upload_progress.min_freq' => null,
                'url_rewriter.tags'        => null,
                'sid_length'               => null,
                'sid_bits_per_character'   => null,
                'trans_sid_hosts'          => null,
                'trans_sid_tags'           => null,
            ],
        ];
    }
    
    /**
     * {@inheritDoc}
     *
     * @param array $options
     * @param \SessionHandlerInterface|null $handler
     */
    public function __construct(array $options = [], ?\SessionHandlerInterface $handler = null)
    {
        $options = array_merge(array_filter(static::config('options'), function ($v) { return $v !== null; }), $options);
        parent::__construct($options, $handler ?? static::config('handler', false), new MetadataBag('_rebet_meta'));
    }
}
