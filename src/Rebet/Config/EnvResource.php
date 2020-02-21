<?php
namespace Rebet\Config;

use Rebet\Common\Arrays;
use Rebet\Common\Exception\LogicException;
use Rebet\Common\Strings;

/**
 * Environment Dependent Resource Loader Class
 *
 * Load the resource file according to the current environment by the following procedure.
 *
 *  1. Load {$dir_path}/{$base_name}.{$suffix} file.
 *  2. Load {$dir_path}/{$base_name}@{$env}.{$suffix} file.
 *  3. Data of 1 is overridden by data of 2 using Arrays::override
 *
 * Furthermore, Rebet\Config\Resource::load() is used for loading resources.
 * So adding a loader to the class will automatically be able to handle the target resource in this class as well .
 *
 * @see Rebet\Config\Resource
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class EnvResource
{
    /**
     * Load the given resource.
     *
     * @param string $env
     * @param string $dir_path
     * @param string|string[]|null $base_names (default: null for all load)
     * @param string $suffix (default: php)
     * @param array $option (default: [])
     * @return array
     * @throws LogicException
     */
    public static function load(string $env, string $dir_path, $base_names = null, string $suffix = 'php', array $option = []) : array
    {
        $base_names = $base_names ?? static::listBaseNames($dir_path, $suffix) ;
        $resource   = [];
        foreach ((array)$base_names as $base_name) {
            $base_resource_path = "{$dir_path}/{$base_name}.{$suffix}";
            $base_resource      = Resource::load($suffix, $base_resource_path, $option);

            $env_resource_path = "{$dir_path}/{$base_name}@{$env}.{$suffix}";
            $env_resource      = Resource::load($suffix, $env_resource_path, $option);

            if ($base_resource === null && $env_resource === null) {
                throw LogicException::by("Resource {$base_name} {$suffix} not found in {$dir_path}.");
            }

            $resource = Arrays::override($resource, $base_resource ?? []);
            $resource = Arrays::override($resource, $env_resource ?? []);
        }
        return $resource;
    }

    /**
     * List base names with given suffix in the directory.
     *
     * @param string $dir_path
     * @param string $suffix
     * @return array
     */
    protected static function listBaseNames(string $dir_path, string $suffix) : array
    {
        $basenames = [];
        $excludes  = ['.', '..'];
        foreach (scandir($dir_path) as $file) {
            if (in_array($file, $excludes)) {
                continue;
            }
            if (!Strings::endsWith($file, ".{$suffix}")) {
                continue;
            }
            if (is_dir("{$dir_path}/{$file}")) {
                continue;
            }
            $basenames[] = Strings::ratrim(Strings::rtrim($file, ".{$suffix}", 1), '@');
        }
        return array_values(array_unique($basenames));
    }
}
