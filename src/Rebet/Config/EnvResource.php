<?php
namespace Rebet\Config;

use Rebet\Common\Arrays;
use Rebet\Common\Exception\LogicException;

/**
 * Environment Dependent Resource Loader Class
 *
 * Load the resource file according to the current environment by the following procedure.
 *
 *  1. Load {$dir_path}/{$base_name}.{$suffix} file.
 *  2. Load {$dir_path}/{$base_name}_{$env}.{$suffix} file.
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
     * @param string $dir_path
     * @param string|array $base_names
     * @param string $env
     * @param string $suffix (default: .php)
     * @param array $option (default: [])
     * @return array
     * @throws LogicException
     */
    public static function load(string $dir_path, $base_names, string $env, string $suffix = 'php', array $option = []) : array
    {
        $resource = [];
        foreach ((array)$base_names as $base_name) {
            $base_resource_path = "{$dir_path}/{$base_name}.{$suffix}";
            $base_resource      = Resource::load($suffix, $base_resource_path, $option);

            $env_resource_path = "{$dir_path}/{$base_name}_{$env}.{$suffix}";
            $env_resource      = Resource::load($suffix, $env_resource_path, $option);

            if ($base_resource === null && $env_resource === null) {
                throw LogicException::by("Resource {$base_name} {$suffix} not found in {$dir_path}.");
            }

            $resource = Arrays::override($resource, $base_resource ?? []);
            $resource = Arrays::override($resource, $env_resource ?? []);
        }
        return $resource;
    }
}
