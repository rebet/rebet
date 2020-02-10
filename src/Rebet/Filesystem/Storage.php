<?php
namespace Rebet\Filesystem;

use League\Flysystem\Adapter\Ftp;
use League\Flysystem\Adapter\Local;
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
        if ($disk = static::$disks[$name] ?? null) {
            return $disk;
        }

        $filesystem = static::config('filesystem');

        return static::$disks[$name] = new $filesystem(
            static::configInstantiate("disks.{$name}", 'adapter'),
            static::config("disks.{$name}.filesystem", false, null)
        );
    }
}
