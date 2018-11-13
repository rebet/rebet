<?php
namespace Rebet\Http\Session\Storage\Handler;

use Rebet\Config\Configurable;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler as SymfonyNativeFileSessionHandler;

/**
 * Native File Session Handler Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class NativeFileSessionHandler extends SymfonyNativeFileSessionHandler
{
    use Configurable;

    public static function defaultConfig() : array
    {
        return [
            'save_path' => ini_get('session.save_path'),
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @param string $save_path (default: depend on configure)
     */
    public function __construct(string $save_path = null)
    {
        parent::__construct($save_path ?? static::config('save_path'));
    }
}
