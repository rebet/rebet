<?php
namespace Rebet\Tools\Resource;

use Rebet\Tools\Utility\Arrays;
use Rebet\Tools\Utility\OverrideOption;
use Rebet\Tools\Utility\Strings;

/**
 * Locale Dependent Resource Loader Class
 *
 * Load the resource file according to the current locale by the following procedure.
 *
 *  1. Load {$loading_path}/{$locale}/{$base_name}.{$suffix} file
 *     Note: if locale contains country part like 'en_US', try to find 'en_US' first and if not exists it try to find 'en'.
 *  2. Arrays::override() while repeating 1 for the given directories
 *
 * Furthermore, Rebet\Tools\Resource\Resource::load() is used for loading resources.
 * So adding a loader to the class will automatically be able to handle the target resource in this class as well .
 *
 * @see Rebet\Tools\Resource\Resource
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
            foreach (array_unique([$locale, Strings::latrim($locale, '_')]) as $search_locale) {
                if (file_exists($resource_path = "{$path}/{$search_locale}/{$base_name}.{$suffix}")) {
                    break;
                }
            }
            $resource = Arrays::override($resource, Resource::load($suffix, $resource_path, $option ?? []) ?? [], [], OverrideOption::PREPEND);
        }

        return $resource;
    }
}
