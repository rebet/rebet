<?php

declare(strict_types=1);

namespace Rebet\Filesystem;

use Override;
use Rebet\Filesystem\Exception\FilesystemException;
use Rebet\Tools\Config\Configurable;
use Rebet\Tools\Exception\LogicException;
use Rebet\Tools\Reflection\Reflector;
use Rebet\Tools\Utility\Path;
use Rebet\Tools\Utility\Strings;
use Rebet\Tools\Utility\Utils;

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

    /**
     * {@inheritDoc}
     * @see https://github.com/rebet/rebet/blob/master/skeltons/app/config/filesystem.lp.php
     */
    #[Override]
    public static function defaultConfig()
    {
        return [
            'private_disk' => null,
            'public_disk'  => null,
            'disks'        => [], // @rebet-instantiable map
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
    private function __construct() {}

    /**
     * Get the given name disk storage.
     *
     * @param  string     $name
     * @return Filesystem
     */
    public static function disk(string $name): Filesystem
    {
        if ($disk = static::$disks[$name] ?? null) {
            return $disk;
        }

        $definition = static::config("disks.{$name}"); // the DotAccessDelegator in the definition is resolved by config()
        $factory    = is_array($definition) ? $definition['@factory'] ?? null : null;
        if ($factory === null) {
            throw new LogicException("Invalid disk '{$name}', the disk must be an instantiation definition array that has '@factory' of " . Filesystem::class . ".");
        }
        if (is_string($factory) && !Strings::contains($factory, '::') && !is_a($factory, Filesystem::class, true)) {
            throw new LogicException("Invalid disk '{$name}', the '@factory' of the disk must be an implementation class of " . Filesystem::class . " but {$factory} given.");
        }
        if (Utils::isBlank($definition['adapter'] ?? null)) {
            throw new LogicException("Invalid disk '{$name}', the 'adapter' of the disk is undefined.");
        }

        // NOTE: The constructor of Filesystem requires the instance of the adapter (see Filesystem::__construct()),
        //       so that the adapter definition is instantiated by Storage after the disk factory is validated.
        $definition['adapter'] = Reflector::instantiate($definition['adapter']);
        $disk                  = Reflector::instantiate($definition);
        if (!$disk instanceof Filesystem) {
            throw new LogicException("Invalid disk '{$name}', the disk must be an instance of " . Filesystem::class . " but " . get_debug_type($disk) . " given.");
        }
        return static::$disks[$name] = $disk;
    }

    /**
     * Get the private disk storage.
     *
     * @return Filesystem
     */
    public static function private(): Filesystem
    {
        return static::disk(static::config('private_disk'));
    }

    /**
     * Get the public disk storage.
     *
     * @return Filesystem
     */
    public static function public(): Filesystem
    {
        return static::disk(static::config('public_disk'));
    }

    /**
     * Reset data of given name storage
     *
     * @param  string|null $name (default: null for all storages)
     * @return void
     */
    public static function reset(string|null $name = null): void
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
     * @param  string                      $from_disk
     * @param  string                      $from_path
     * @param  string                      $to_disk
     * @param  string|null                 $to_path   (default: null for use $from_path, as it is)
     * @param  string|array<string, mixed> $options   (default: [])
     * @param  bool                        $replace   (default: false)
     * @return void
     */
    public static function copy(string $from_disk, string $from_path, string $to_disk, string|null $to_path = null, $options = [], bool $replace = false): void
    {
        $from = static::disk($from_disk);
        $to   = static::disk($to_disk);
        $to_path ??= $from_path;
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
            $path = Path::normalize($to_path . '/' . Strings::ltrim($content, Strings::ltrim($from_path, '/')));
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
     * @param  string                      $from_disk
     * @param  string                      $from_path
     * @param  string                      $to_disk
     * @param  string|null                 $to_path   (default: null for use $from_path, as it is)
     * @param  string|array<string, mixed> $options   (default: [])
     * @param  bool                        $replace   (default: false)
     * @return void
     */
    public static function move(string $from_disk, string $from_path, string $to_disk, string|null $to_path = null, $options = [], bool $replace = false): void
    {
        static::copy($from_disk, $from_path, $to_disk, $to_path, $options, $replace);
        static::disk($from_disk)->delete($from_path);
    }

    /**
     * Publish the given private storage contents to public storage.
     *
     * @param  string      $from contents path
     * @param  string|null $to   contents path (default: null for use $from contents path, as it is)
     * @return void
     */
    public static function publish(string $from, string|null $to = null): void
    {
        static::move(static::config('private_disk'), $from, static::config('public_disk'), $to, Filesystem::VISIBILITY_PUBLIC, true);
    }
}
