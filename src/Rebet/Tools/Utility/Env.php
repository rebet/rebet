<?php
namespace Rebet\Tools\Utility;

use Rebet\Tools\Config\Config;
use Rebet\Tools\Config\ConfigPromise;

/**
 * Env Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Env
{
    /**
     * No instantiation
     */
    private function __construct()
    {
    }

    /**
     * Gets the value of an environment variable.
     *
     * @param string $name
     * @param mixed $default (default: null)
     * @return mixed
     */
    public static function get(string $name, $default = null)
    {
        $env = getenv($name, true);
        $env = $env === false ? getenv($name) : $env ;
        $env = $env === false ? null : $env ;
        return static::convert($env) ?? static::resolve($default) ;
    }

    /**
     * Create a promise for lazy evaluating configuration value to get given name environment variable.
     *
     * @param string $name
     * @param mixed $default (default: null)
     * @param bool $only_once (default: true)
     * @return ConfigPromise
     */
    public static function promise(string $name, $default = null, bool $only_once = true) : ConfigPromise
    {
        return Config::promise(function () use ($name, $default) { return static::get($name, $default); }, $only_once);
    }

    /**
     * Resolve given default value if it is Closure.
     *
     * @param mixed $default
     * @return mixed
     */
    protected static function resolve($default)
    {
        return $default instanceof \Closure ? $default() : $default ;
    }

    /**
     * Convert string boolean and null value to bool/null type.
     *
     * @param [type] $value
     * @return void
     */
    protected static function convert($value)
    {
        if ($value === null || !is_string($value)) {
            return $value;
        }

        switch (strtolower($value)) {
            case 'null': return null;
            case 'true': return true;
            case 'false': return false;
        }

        if (preg_match('/\A([\'"])(.*)\1\z/u', $value, $matches)) {
            return $matches[2];
        }

        return $value;
    }
}
