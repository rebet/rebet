<?php
namespace Rebet\Filesystem;

use League\Flysystem\Adapter\Ftp;
use League\Flysystem\Adapter\Local;
use Rebet\Common\Exception\LogicException;
use Rebet\Common\Reflector;
use Rebet\Config\Configurable;

/**
 * Storage Class
 *
 * https://flysystem.thephpleague.com/v1/docs/usage/setup/
 * https://laravel.com/docs/5.6/filesystem
 * https://www.ritolab.com/entry/7
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Storage
{
    use Configurable;

    public static function defaultConfig()
    {
        return [
            'filesystem' => BuiltinFilesystem::class,
            'default'    => 'local',
            'disks'      => [
                'local' => [
                    'adapter'      => Local::class,
                    'root'         => null,

                    // === Optional Config Settings ===
                    // 'writeFlags'   => LOCK_EX,
                    // 'linkHandling' => Local::DISALLOW_LINKS,
                    // 'permissions'  => [],

                    // === Filesystem Global Configuration ===
                    'filesystem'   => null,
                ],
                'public' => [
                    'adapter'      => Local::class,
                    'root'         => null,

                    // === Optional Config Settings ===
                    // 'writeFlags'   => LOCK_EX,
                    // 'linkHandling' => Local::DISALLOW_LINKS,
                    // 'permissions'  => [],

                    // === Filesystem Global Configuration ===
                    'filesystem'   => [
                        'visibility' => 'public',
                        'url'        => null,
                    ],
                ],
                // // Sample of ftp settings
                // 'ftp' => [
                //     'adapter' => Ftp::class,
                //     'config'  => [
                //         'host'     => null,
                //         'username' => null,
                //         'password' => null,
                //
                //         // // optional config settings
                //         // 'port'                 => 21,
                //         // 'root'                 => '/path/to/root',
                //         // 'passive'              => true,
                //         // 'ssl'                  => true,
                //         // 'timeout'              => 30,
                //         // 'ignorePassiveAddress' => false,
                //     ],
                // ],
            ],
        ];
    }

    /**
     * Storage disks (Filesystem) list
     *
     * @var Filesystem[] ['name' => Filesystem]
     */
    protected static $disks = [];

    /**
     * No instantiation
     */
    private function __construct()
    {
    }

    /**
     * Get the given name storage.
     *
     * @param string|null $name (default: depend on configure)
     * @return self
     */
    public static function disk(?string $name = null) : Filesystem
    {
        $name = $name ?? static::config('default') ;
        $disk = static::$disks[$name] ?? null;
        if ($disk !== null) {
            return $disk;
        }

        $conf = static::config("disks.{$name}", false);
        if ($conf === null) {
            throw LogicException::by("Unable to create '{$name}' filesystem Storage. Undefined configure 'Rebet\Filesystem\Storage.disks.{$name}'.");
        }
        if (!isset($conf['adapter'])) {
            throw LogicException::by("Unable to create '{$name}' filesystem Storage. Adapter is undefined.");
        }
        $adapter    = $conf['adapter'];
        $filesystem = static::config('filesystem');
        $disk       = new $filesystem(
            is_callable($adapter) ? call_user_func($adapter, $name) : (is_string($adapter) ? Reflector::create($adapter, $conf) : $adapter),
            $conf['filesystem'] ?? null
        );

        return static::$disks[$name] = $disk;
    }
}
