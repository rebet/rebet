<?php
namespace Rebet\Config;

use Rebet\Common\Arrays;

/**
 * Locale Dependent Resource Loader Class
 *
 * Load the resource file according to the current locale by the following procedure.
 *
 *  1. Load {$loading_path}/{$locale}/{$base_name}.{$suffix} file
 *  2. Arrays::override() while repeating 1 for the given directories
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
class LocaleResource
{
    /**
     * Load the given resource.
     *
     * @param string|array $loading_path
     * @param string $locale
     * @param string $base_name
     * @param string $suffix (default: .php)
     * @param array $option (default: [])
     * @return array
     * @throws LogicException
     */
    public static function load($loading_path, string $locale, string $base_name, string $suffix = 'php', array $option = []) : array
    {
        $loading_path = (array)$loading_path;

        $resource = [];
        foreach ($loading_path as $path) {
            $resource_path = "{$path}/{$locale}/{$base_name}{$suffix}";
            $resource      = Arrays::override($resource, Resource::load($suffix, $resource_path, $option ?? []) ?? []);
        }

        return $resource;
    }
}
