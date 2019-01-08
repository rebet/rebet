<?php
namespace Rebet\Config;

use Rebet\Common\Exception\LogicException;

/**
 * Resource Class
 *
 * Currently supported resources (extension) are as follows.
 *
 *  - php
 *    => Return the result of requirement of specified php file.
 *    => Option: none
 *
 *  - json
 *    => Returns the result of json_decode of the specified json file.
 *    => Option: none
 *
 *  - ini
 *    => Returns the result of parse_ini_file of specified ini file.
 *    => Option:
 *         process_sections => bool          (default: true)
 *         scanner_mode     => INI_SCANNER_* (default: INI_SCANNER_TYPED)
 *
 *  - txt
 *    => Returns the result of explode of the specified txt file.
 *    => Option:
 *         delimiter => string (default: \n)
 *
 * The behavior of the above resource loader can be changed by overwriting loader closure in Config setting.
 * Also, new resources can be added by defining loader closure in Config setting.
 *
 * ex)
 * Resource::setLoader('yaml', function(string $path, array $option) {
 *     return Symfony\Component\Yaml\Yaml::parse(\file_get_contents($path));
 * })
 *
 * Otherwise,
 *
 * Config::application([
 *     Rebet\Config\Resource::class => {
 *         'loader' => [
 *             'yaml' => function(string $path, array $option) : array {
 *                  return Symfony\Component\Yaml\Yaml::parse(\file_get_contents($path));
 *             }
 *         ]
 *     }
 * ]);
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Resource
{
    use Configurable;

    public static function defaultConfig()
    {
        return [
            'loader' => [
                'php'  => function (string $path, array $option) {
                    if (!\file_exists($path)) {
                        return null;
                    }
                    $resource = require $path;
                    return is_array($resource) ? $resource : [] ;
                },
                'json' => function (string $path, array $option) {
                    if (!\file_exists($path)) {
                        return null;
                    }
                    return \json_decode(\file_get_contents($path), true);
                },
                'ini'  => function (string $path, array $option) {
                    if (!\file_exists($path)) {
                        return null;
                    }
                    return \parse_ini_file($path, $option['process_sections'] ?? true, $option['scanner_mode'] ?? INI_SCANNER_TYPED);
                },
                'txt'  => function (string $path, array $option) {
                    if (!\file_exists($path)) {
                        return null;
                    }
                    return \explode($option['delimiter'] ?? "\n", \file_get_contents($path));
                },
            ]
        ];
    }

    /**
     * Register the resource loader.
     *
     * @param string $suffix
     * @param \Closure
     * @return mixed
     */
    public static function setLoader(string $suffix, \Closure $loader)
    {
        self::setConfig(['loader' => [$suffix => $loader]]);
    }

    /**
     * Load the specified resource.
     *
     * @param string $type
     * @param string $path
     * @param array $option (default: [])
     * @return mixed
     * @throws LogicException
     */
    public static function load(string $type, string $path, array $option = [])
    {
        $loader = self::config("loader.{$type}", false);
        if (empty($loader) || !\is_callable($loader)) {
            throw LogicException::by("Unsupported file type [$type]. Please set loader to Rebet\Config\Resource class.");
        }
        return $loader($path, $option);
    }
}
