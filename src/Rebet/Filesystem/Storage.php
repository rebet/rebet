<?php
namespace Rebet\Filesystem;

use League\Flysystem\Adapter\Ftp;
use League\Flysystem\Adapter\Local;
use Rebet\Tools\Utility\Path;
use Rebet\Tools\Utility\Strings;
use Rebet\Tools\Config\Configurable;
use Rebet\Filesystem\Exception\FilesystemException;

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
            'filesystem'   => BuiltinFilesystem::class,
            'private_disk' => 'private',
            'public_disk'  => 'public',
            'disks'        => [
                'private'  => [
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
     * Get the given name disk storage.
     *
     * @param string $name
     * @return self
     */
    public static function disk(string $name) : Filesystem
    {
        if ($disk = static::$disks[$name] ?? null) {
            return $disk;
        }

        $filesystem = static::config('filesystem');

        return static::$disks[$name] = new $filesystem(
            static::configInstantiate("disks.{$name}", 'adapter'),
            static::config("disks.{$name}.filesystem", false, null)
        );
    }

    /**
     * Get the private disk storage.
     *
     * @return Filesystem
     */
    public static function private() : Filesystem
    {
        return static::disk(static::config('private_disk'));
    }

    /**
     * Get the public disk storage.
     *
     * @return Filesystem
     */
    public static function public() : Filesystem
    {
        return static::disk(static::config('public_disk'));
    }

    /**
     * Clean data of given name storage
     *
     * @param string|null $name (default: null for all storages)
     * @return void
     */
    public static function clean(?string $name = null) : void
    {
        if ($name) {
            $disk = static::$disks[$name] ?? null;
            if ($disk) {
                $disk->clean();
                unset(static::$disks[$name]);
            }
            return;
        }


        foreach (static::$disks as $disk) {
            $disk->clean();
        }
        static::$disks = [];
    }

    /**
     * Copy contents between different disks.
     *
     * @param string $from_disk
     * @param string $from_path
     * @param string $to_disk
     * @param string|null $to_path (default: null for use $from_path, as it is)
     * @param string|array $options (default: [])
     * @param bool $replace (default: false)
     * @return void
     */
    public static function copy(string $from_disk, string $from_path, string $to_disk, ?string $to_path = null, $options = [], bool $replace = false) : void
    {
        $from    = static::disk($from_disk);
        $to      = static::disk($to_disk);
        $to_path = $to_path ?? $from_path;
        if ($replace) {
            $to->delete($to_path);
        } else {
            if ($to->exists($to_path)) {
                throw new FilesystemException("Can not copy `{$from_disk}:{$from_path}` to `{$to_disk}:{$to_path}`, `{$to_disk}:{$to_path}` already exists.");
            }
        }
        if (!$from->exists($from_path)) {
            return;
        }

        if ($from->isFile($from_path)) {
            $to->put($to_path, $from->readStream($from_path), $options);
            return;
        }

        foreach ($from->ls($from_path, '*', null, true) as $content) {
            $path = Path::normalize($to_path.'/'.Strings::ltrim($content, Strings::ltrim($from_path, '/')));
            if ($from->isFile($content)) {
                $to->put($path, $from->readStream($content), $options);
                continue;
            }

            $to->mkdir($path);
        }
    }

    /**
     * Move contents between different disks.
     *
     * @param string $from_disk
     * @param string $from_path
     * @param string $to_disk
     * @param string|null $to_path (default: null for use $from_path, as it is)
     * @param string|array $options (default: [])
     * @param bool $replace (default: false)
     * @return void
     */
    public static function move(string $from_disk, string $from_path, string $to_disk, ?string $to_path = null, $options = [], bool $replace = false) : void
    {
        static::copy($from_disk, $from_path, $to_disk, $to_path, $options, $replace);
        static::disk($from_disk)->delete($from_path);
    }

    /**
     * Publish the given private storage contents to public storage.
     *
     * @param string $from contents path
     * @param string|null $to contents path (default: null for use $from contents path, as it is)
     * @return void
     */
    public static function publish(string $from, ?string $to = null) : void
    {
        static::move(static::config('private_disk'), $from, static::config('public_disk'), $to, Filesystem::VISIBILITY_PUBLIC, true);
    }
}
